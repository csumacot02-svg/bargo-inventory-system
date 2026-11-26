<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\InventoryItem;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Services\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StockInController extends Controller
{
    public function __construct(private InventoryStockService $stockService) {}

    public function index(): View
    {
        $stockIns = StockIn::query()
            ->with(['supplier', 'receiver', 'items'])
            ->latest()
            ->paginate(10);

        return view('stock-ins.index', compact('stockIns'));
    }

    public function create(): View
    {
        return view('stock-ins.create', [
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'items' => InventoryItem::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'received_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.expiration_date' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $stockIn = StockIn::create([
                'reference_no' => 'SI-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
                'supplier_id' => $data['supplier_id'] ?? null,
                'received_by' => $request->user()->id,
                'received_date' => $data['received_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $row) {
                $stockIn->items()->create($row);
            }

            $this->stockService->applyStockIn($stockIn->fresh('items'), $request->user());

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'stock_in',
                'module' => 'stock-in',
                'description' => 'Recorded stock-in '.$stockIn->reference_no.' (stock auto-updated)',
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route('stock-ins.index')->with('success', 'Stock-in saved. Inventory quantities updated automatically.');
    }

    public function show(StockIn $stockIn): View
    {
        $stockIn->load(['supplier', 'receiver', 'voidedBy', 'items.inventoryItem']);

        return view('stock-ins.show', compact('stockIn'));
    }

    public function void(Request $request, StockIn $stockIn): RedirectResponse
    {
        abort_unless($request->user()->canVoidStockIn(), 403);

        $data = $request->validate([
            'void_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->stockService->voidStockIn($stockIn, $request->user(), $data['void_reason']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'stock_in_void',
            'module' => 'stock-in',
            'description' => 'Voided stock-in '.$stockIn->reference_no,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('stock-ins.show', $stockIn)->with('success', 'Stock-in voided and quantities reversed.');
    }
}
