<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryRequest;
use App\Models\StockIn;
use App\Models\User;
use App\Services\ConsolidatedBargoReportBuilder;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->string('type', 'inventory')->toString();

        $inventory = InventoryItem::query()
            ->with('category')
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->orderBy('name')
            ->get();

        $stockIns = StockIn::query()
            ->with(['supplier', 'items'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('received_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('received_date', '<=', $request->date('to')))
            ->latest('received_date')
            ->get();

        $requests = InventoryRequest::query()
            ->with(['requester', 'items'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->get();

        $lowStock = $inventory->filter->isLowStock();
        $categories = Category::query()->orderBy('name')->get();
        $signatories = User::reportSignatories();
        $inventoryValue = InventoryItem::totalOnHandValue();
        $productSales = InventoryRequest::releasedSalesTotal(
            $request->filled('from') ? $request->date('from') : now()->startOfYear(),
            $request->filled('to') ? $request->date('to') : now(),
        );

        return view('reports.index', compact(
            'type',
            'inventory',
            'stockIns',
            'requests',
            'lowStock',
            'categories',
            'signatories',
            'inventoryValue',
            'productSales',
        ));
    }

    public function consolidatedBargo(Request $request, ConsolidatedBargoReportBuilder $builder): View
    {
        $year = (int) $request->input('year', now()->year);
        $report = $builder->build($year);

        return view('reports.consolidated-bargo', compact('report', 'year'));
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->canAccessReports(), 403);

        $type = $request->string('type', 'inventory')->toString();
        $filename = 'bargo-'.$type.'-'.now()->format('Ymd-His').'.csv';

        Audit::log(
            $request->user(),
            'export',
            'reports',
            'Exported '.$type.' report as '.$filename,
            $request
        );

        $callback = function () use ($request, $type) {
            $out = fopen('php://output', 'w');

            if ($type === 'low') {
                fputcsv($out, ['Code', 'Item', 'Qty', 'Reorder']);
                InventoryItem::query()->orderBy('name')->get()->filter->isLowStock()->each(function ($item) use ($out) {
                    fputcsv($out, [$item->item_code, $item->name, $item->quantity_on_hand, $item->reorder_level]);
                });
            } elseif ($type === 'stock-in') {
                fputcsv($out, ['Reference', 'Date', 'Supplier', 'Lines', 'Voided']);
                StockIn::query()->with(['supplier', 'items'])
                    ->when($request->filled('from'), fn ($q) => $q->whereDate('received_date', '>=', $request->date('from')))
                    ->when($request->filled('to'), fn ($q) => $q->whereDate('received_date', '<=', $request->date('to')))
                    ->latest('received_date')
                    ->get()
                    ->each(function ($row) use ($out) {
                        fputcsv($out, [
                            $row->reference_no,
                            optional($row->received_date)->toDateString(),
                            $row->supplier?->name,
                            $row->items->count(),
                            $row->voided_at ? 'Yes' : 'No',
                        ]);
                    });
            } elseif ($type === 'requests') {
                fputcsv($out, ['Request No', 'Requested by', 'Status', 'Payment', 'OR', 'Amount paid', 'Due date', 'Date']);
                InventoryRequest::query()->with('requester')
                    ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
                    ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
                    ->latest()
                    ->get()
                    ->each(function ($row) use ($out) {
                        fputcsv($out, [
                            $row->request_no,
                            $row->requester?->name,
                            $row->status,
                            $row->payment_status,
                            $row->or_number,
                            $row->amount_paid,
                            optional($row->due_date)->toDateString(),
                            $row->created_at->toDateTimeString(),
                        ]);
                    });
            } else {
                fputcsv($out, ['Code', 'Item', 'Category', 'Qty', 'Unit Price', 'Value', 'Reorder', 'Condition']);
                $totalValue = 0;
                InventoryItem::query()->with('category')->orderBy('name')->get()->each(function ($item) use ($out, &$totalValue) {
                    $value = $item->onHandValue();
                    $totalValue += $value;
                    fputcsv($out, [
                        $item->item_code,
                        $item->name,
                        $item->category?->name,
                        $item->quantity_on_hand,
                        $item->unit_price,
                        number_format($value, 2, '.', ''),
                        $item->reorder_level,
                        $item->condition,
                    ]);
                });
                fputcsv($out, ['', '', '', '', 'Total inventory value', number_format($totalValue, 2, '.', ''), '', '']);
                fputcsv($out, ['', '', '', '', 'Product sales', number_format(InventoryRequest::releasedSalesTotal(
                    $request->filled('from') ? $request->date('from') : now()->startOfYear(),
                    $request->filled('to') ? $request->date('to') : now(),
                ), 2, '.', ''), '', '']);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
