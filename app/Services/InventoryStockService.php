<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\InventoryRequest;
use App\Models\StockIn;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryStockService
{
    /**
     * Increase stock after a successful stock-in transaction.
     */
    public function applyStockIn(StockIn $stockIn, User $actor): void
    {
        DB::transaction(function () use ($stockIn, $actor) {
            $stockIn->loadMissing('items.inventoryItem');

            foreach ($stockIn->items as $line) {
                $this->adjust(
                    itemId: $line->inventory_item_id,
                    change: (float) $line->quantity,
                    type: 'stock_in',
                    actor: $actor,
                    referenceType: StockIn::class,
                    referenceId: $stockIn->id,
                    notes: 'Stock-in '.$stockIn->reference_no,
                    expirationDate: $line->expiration_date?->toDateString(),
                    stockInId: $stockIn->id,
                    lotSource: 'stock_in',
                );
            }
        });
    }

    public function voidStockIn(StockIn $stockIn, User $actor, string $reason): void
    {
        if ($stockIn->isVoided()) {
            throw new RuntimeException('This stock-in is already voided.');
        }

        DB::transaction(function () use ($stockIn, $actor, $reason) {
            $locked = StockIn::query()->lockForUpdate()->findOrFail($stockIn->id);

            if ($locked->voided_at) {
                throw new RuntimeException('This stock-in is already voided.');
            }

            $lots = InventoryLot::query()
                ->where('stock_in_id', $locked->id)
                ->lockForUpdate()
                ->get();

            foreach ($lots as $lot) {
                if ((float) $lot->quantity_remaining + 0.0001 < (float) $lot->quantity_received) {
                    throw new RuntimeException(
                        'Cannot void '.$locked->reference_no
                        .' — some of this delivery has already been issued. Record an adjustment instead.'
                    );
                }
            }

            foreach ($locked->items as $line) {
                $this->adjust(
                    itemId: $line->inventory_item_id,
                    change: -1 * (float) $line->quantity,
                    type: 'stock_in_void',
                    actor: $actor,
                    referenceType: StockIn::class,
                    referenceId: $locked->id,
                    notes: 'Void '.$locked->reference_no.': '.$reason,
                    skipLotCreate: true,
                );
            }

            foreach ($lots as $lot) {
                $lot->update(['quantity_remaining' => 0]);
                $lot->inventoryItem?->syncExpirationFromLots();
            }

            $locked->update([
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'void_reason' => $reason,
            ]);

            $stockIn->refresh();
        });
    }

    /**
     * Decrease stock once for a successful release / paid issuance.
     * Safe to call again — skips if already deducted.
     */
    public function applyRequestStockOut(InventoryRequest $request, User $actor, string $trigger): void
    {
        if ($request->stock_deducted) {
            return;
        }

        DB::transaction(function () use ($request, $actor, $trigger) {
            $locked = InventoryRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($locked->stock_deducted) {
                return;
            }

            $locked->load('items.inventoryItem');

            foreach ($locked->items as $line) {
                $qty = (float) ($line->quantity_approved ?? $line->quantity_requested);

                $this->adjust(
                    itemId: $line->inventory_item_id,
                    change: -$qty,
                    type: $trigger === 'payment' ? 'payment_sale' : 'release',
                    actor: $actor,
                    referenceType: InventoryRequest::class,
                    referenceId: $locked->id,
                    notes: ucfirst($trigger).' for '.$locked->request_no,
                );
            }

            $locked->update(['stock_deducted' => true]);
            $request->refresh();
        });
    }

    public function recordOpeningBalance(InventoryItem $item, User $actor, float $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $this->adjust(
            itemId: $item->id,
            change: $qty,
            type: 'opening_balance',
            actor: $actor,
            referenceType: InventoryItem::class,
            referenceId: $item->id,
            notes: 'Opening balance',
            expirationDate: $item->expiration_date?->toDateString(),
            lotSource: 'opening_balance',
            alreadyAppliedToOnHand: true,
        );
    }

    public function adjust(
        int $itemId,
        float $change,
        string $type,
        ?User $actor = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?string $expirationDate = null,
        ?int $stockInId = null,
        string $lotSource = 'adjustment',
        bool $skipLotCreate = false,
        bool $alreadyAppliedToOnHand = false,
    ): InventoryItem {
        $item = InventoryItem::query()->lockForUpdate()->findOrFail($itemId);
        $before = (float) $item->quantity_on_hand;
        $after = $alreadyAppliedToOnHand ? $before : $before + $change;

        if ($after < -0.0001) {
            throw new RuntimeException("Insufficient stock for {$item->name}. Available: {$before}.");
        }

        if ($change < 0 && $item->is_perishable && $item->isExpired() && ! in_array($type, ['adjustment', 'spoilage', 'stock_in_void'], true)) {
            throw new RuntimeException("{$item->name} is perishable and expired. Do not issue — adjust/dispose first.");
        }

        if (! $alreadyAppliedToOnHand) {
            $item->update(['quantity_on_hand' => $after]);
        }

        if ($item->is_perishable && $item->isExpired() && $item->condition !== 'expired') {
            $item->update(['condition' => 'expired']);
        }

        if ($change > 0 && ! $skipLotCreate) {
            InventoryLot::query()->create([
                'inventory_item_id' => $item->id,
                'stock_in_id' => $stockInId,
                'quantity_received' => $change,
                'quantity_remaining' => $change,
                'expiration_date' => $expirationDate ?: $item->expiration_date,
                'source' => $lotSource,
            ]);
            $item->syncExpirationFromLots();
        }

        if ($change < 0 && ! $skipLotCreate) {
            $this->consumeLotsFifo($item, abs($change));
        }

        StockMovement::create([
            'inventory_item_id' => $item->id,
            'user_id' => $actor?->id,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity_change' => $change,
            'quantity_before' => $before,
            'quantity_after' => $alreadyAppliedToOnHand ? $before : $after,
            'notes' => $notes,
        ]);

        return $item->fresh();
    }

    private function consumeLotsFifo(InventoryItem $item, float $qty): void
    {
        $remaining = $qty;

        $lots = InventoryLot::query()
            ->where('inventory_item_id', $item->id)
            ->where('quantity_remaining', '>', 0)
            ->orderByRaw('expiration_date is null')
            ->orderBy('expiration_date')
            ->lockForUpdate()
            ->get();

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((float) $lot->quantity_remaining, $remaining);
            $lot->update(['quantity_remaining' => round((float) $lot->quantity_remaining - $take, 2)]);
            $remaining = round($remaining - $take, 2);
        }

        $item->syncExpirationFromLots();
    }
}
