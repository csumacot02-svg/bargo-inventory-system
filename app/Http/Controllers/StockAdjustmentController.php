<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\InventoryItem;
use App\Services\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class StockAdjustmentController extends Controller
{
    public function __construct(private InventoryStockService $stockService) {}

    public function create(Request $request): View
    {
        abort_unless($request->user()->canAdjustStock(), 403);

        return view('adjustments.create', [
            'items' => InventoryItem::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedId' => $request->integer('item'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canAdjustStock(), 403);

        $data = $request->validate([
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'mode' => ['required', 'in:count,spoilage,damage,correction'],
            'quantity' => ['required', 'numeric'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $item = InventoryItem::query()->findOrFail($data['inventory_item_id']);
        $mode = $data['mode'];
        $inputQty = (float) $data['quantity'];

        $change = match ($mode) {
            'count' => $inputQty - (float) $item->quantity_on_hand,
            'spoilage', 'damage' => -abs($inputQty),
            default => $inputQty,
        };

        if (abs($change) < 0.0001) {
            return back()->withInput()->with('error', 'No quantity change to post.');
        }

        $type = match ($mode) {
            'spoilage' => 'spoilage',
            'damage' => 'adjustment',
            'count' => 'physical_count',
            default => 'adjustment',
        };

        try {
            $this->stockService->adjust(
                itemId: $item->id,
                change: $change,
                type: $type,
                actor: $request->user(),
                referenceType: InventoryItem::class,
                referenceId: $item->id,
                notes: ucfirst($mode).': '.$data['reason'],
                lotSource: $mode,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'stock_adjustment',
            'module' => 'inventory',
            'description' => "{$type} on {$item->item_code}: {$change} ({$data['reason']})",
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('inventory.show', $item)
            ->with('success', 'Stock adjustment posted to the ledger.');
    }
}
