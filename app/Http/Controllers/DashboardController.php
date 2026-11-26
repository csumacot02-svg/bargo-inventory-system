<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryRequest;
use App\Models\RequestInstallment;
use App\Models\StockIn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $requestScope = function ($query) use ($user) {
            if (! $user->canViewAllRequests()) {
                $query->where('requester_id', $user->id);
            }
        };

        $totalItems = InventoryItem::query()->count();
        $lowStock = InventoryItem::query()
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->count();

        $totalRequests = InventoryRequest::query()->tap($requestScope)->count();
        $pendingRequests = InventoryRequest::query()->where('status', InventoryRequest::STATUS_PENDING)->tap($requestScope)->count();
        $approvedRequests = InventoryRequest::query()->where('status', InventoryRequest::STATUS_APPROVED)->tap($requestScope)->count();
        $releasedRequests = InventoryRequest::query()->where('status', InventoryRequest::STATUS_RELEASED)->tap($requestScope)->count();
        $completedRequests = InventoryRequest::query()->where('status', InventoryRequest::STATUS_COMPLETED)->tap($requestScope)->count();

        $stockInsThisMonth = StockIn::query()
            ->whereMonth('received_date', now()->month)
            ->whereYear('received_date', now()->year)
            ->count();

        $inventoryValue = InventoryItem::totalOnHandValue();
        $productSales = InventoryRequest::releasedSalesTotal(now()->startOfYear(), now());
        $productSalesMonth = InventoryRequest::releasedSalesTotal(now()->startOfMonth(), now());

        $recentItems = InventoryItem::query()
            ->with('category')
            ->latest()
            ->take(6)
            ->get();

        $lowStockItems = InventoryItem::query()
            ->with('category')
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->orderBy('quantity_on_hand')
            ->take(6)
            ->get();

        $recentRequests = InventoryRequest::query()
            ->with(['requester', 'items.inventoryItem'])
            ->when(! $user->canViewAllRequests(), fn ($q) => $q->where('requester_id', $user->id))
            ->latest()
            ->take(6)
            ->get();

        $categoryBreakdown = Category::query()
            ->withCount(['items as item_count'])
            ->having('item_count', '>', 0)
            ->orderByDesc('item_count')
            ->take(6)
            ->get()
            ->map(fn (Category $category) => [
                'label' => $category->name,
                'count' => (int) $category->item_count,
            ])
            ->values();

        $uncategorized = InventoryItem::query()->whereNull('category_id')->count();
        if ($uncategorized > 0) {
            $categoryBreakdown->push([
                'label' => 'Uncategorized',
                'count' => $uncategorized,
            ]);
        }

        $categoryTotal = max(1, (int) $categoryBreakdown->sum('count'));

        $requestTrend = $this->monthlyRequestTrend($requestScope);
        $salesTrend = $this->monthlySalesTrend();

        $upcomingDues = RequestInstallment::query()
            ->with(['inventoryRequest'])
            ->whereHas('inventoryRequest', function ($query) use ($user, $requestScope) {
                $query->where('payment_status', '!=', 'paid')
                    ->whereIn('status', [
                        InventoryRequest::STATUS_APPROVED,
                        InventoryRequest::STATUS_RELEASED,
                        InventoryRequest::STATUS_COMPLETED,
                    ]);
                $requestScope($query);
            })
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '>=', now()->subDays(2)->toDateString())
            ->whereDate('due_date', '<=', now()->addDays(21)->toDateString())
            ->orderBy('due_date')
            ->take(8)
            ->get();

        $dueDates = RequestInstallment::query()
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('due_date', '<=', now()->endOfMonth()->addMonth()->toDateString())
            ->pluck('due_date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();

        $singleDueDates = InventoryRequest::query()
            ->tap($requestScope)
            ->where('payment_type', 'credit')
            ->where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereDoesntHave('installments')
            ->whereDate('due_date', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('due_date', '<=', now()->endOfMonth()->addMonth()->toDateString())
            ->pluck('due_date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->all();

        $calendarDueDates = collect($dueDates)
            ->merge($singleDueDates)
            ->unique()
            ->values()
            ->all();

        $recentActivities = ActivityLog::query()
            ->with('user')
            ->latest()
            ->take(8)
            ->get();

        $dashboardTitle = 'Dashboard';
        $dashboardDate = now()->format('F j, l');

        $chartPayload = [
            'requestTrend' => $requestTrend,
            'salesTrend' => $salesTrend,
            'categories' => [
                'labels' => $categoryBreakdown->pluck('label')->all(),
                'values' => $categoryBreakdown->pluck('count')->all(),
                'total' => $categoryTotal,
            ],
            'dueDates' => $calendarDueDates,
        ];

        return view('dashboard', compact(
            'totalItems',
            'lowStock',
            'totalRequests',
            'pendingRequests',
            'approvedRequests',
            'releasedRequests',
            'completedRequests',
            'stockInsThisMonth',
            'inventoryValue',
            'productSales',
            'productSalesMonth',
            'recentItems',
            'lowStockItems',
            'recentRequests',
            'categoryBreakdown',
            'categoryTotal',
            'upcomingDues',
            'recentActivities',
            'dashboardTitle',
            'dashboardDate',
            'chartPayload',
        ));
    }

    /**
     * @param  callable(\Illuminate\Database\Eloquent\Builder): void  $requestScope
     * @return array{labels: list<string>, values: list<int>}
     */
    private function monthlyRequestTrend(callable $requestScope): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $rows = InventoryRequest::query()
            ->tap($requestScope)
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->pluck('total', 'ym');

        $labels = [];
        $values = [];

        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M');
            $values[] = (int) ($rows[$key] ?? 0);
        }

        return compact('labels', 'values');
    }

    /**
     * @return array{labels: list<string>, sales: list<float>, stockIns: list<int>}
     */
    private function monthlySalesTrend(): array
    {
        $start = now()->startOfMonth()->subMonths(5);

        $salesRows = DB::table('inventory_request_items')
            ->join('inventory_requests', 'inventory_requests.id', '=', 'inventory_request_items.inventory_request_id')
            ->leftJoin('inventory_items', 'inventory_items.id', '=', 'inventory_request_items.inventory_item_id')
            ->whereIn('inventory_requests.status', [
                InventoryRequest::STATUS_RELEASED,
                InventoryRequest::STATUS_COMPLETED,
            ])
            ->whereNotNull('inventory_requests.released_at')
            ->where('inventory_requests.released_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(inventory_requests.released_at, '%Y-%m') as ym")
            ->selectRaw('COALESCE(SUM(COALESCE(inventory_request_items.quantity_approved, inventory_request_items.quantity_requested) * COALESCE(inventory_items.unit_price, 0)), 0) as total')
            ->groupByRaw("DATE_FORMAT(inventory_requests.released_at, '%Y-%m')")
            ->pluck('total', 'ym');

        $stockInRows = StockIn::query()
            ->where('received_date', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(received_date, '%Y-%m') as ym, COUNT(*) as total")
            ->groupByRaw("DATE_FORMAT(received_date, '%Y-%m')")
            ->pluck('total', 'ym');

        $labels = [];
        $sales = [];
        $stockIns = [];

        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M');
            $sales[] = round((float) ($salesRows[$key] ?? 0), 2);
            $stockIns[] = (int) ($stockInRows[$key] ?? 0);
        }

        return compact('labels', 'sales', 'stockIns');
    }
}
