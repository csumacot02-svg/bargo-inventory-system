<?php

namespace App\Services;

use App\Models\InventoryRequest;
use App\Models\User;
use App\Support\AmountInWords;

class OrderOfPaymentDocumentBuilder
{
    private const DETAIL_ROWS = 6;

    public function build(InventoryRequest $inventoryRequest): array
    {
        $inventoryRequest->loadMissing([
            'requester',
            'reviewer',
            'items.inventoryItem.category',
            'installments',
            'payments',
        ]);

        $formDate = $inventoryRequest->created_at;
        $releasedAt = $inventoryRequest->released_at;

        $detailLines = [];

        foreach ($inventoryRequest->items as $line) {
            $item = $line->inventoryItem;
            $qty = (float) ($line->quantity_approved ?? $line->quantity_requested);
            $price = (float) ($item?->unit_price ?? 0);

            $detailLines[] = [
                'collection_type' => config('bargo.payment_order.default_collection_type'),
                'code' => $item?->item_code ?? '—',
                'quantity' => $qty,
                'particular' => $item?->name ?? 'Inventory item',
                'unit_price' => $price,
                'total' => round($qty * $price, 2),
            ];
        }

        while (count($detailLines) < self::DETAIL_ROWS) {
            $detailLines[] = [
                'collection_type' => '',
                'code' => '',
                'quantity' => '',
                'particular' => '',
                'unit_price' => '',
                'total' => '',
            ];
        }

        $grandTotal = round($inventoryRequest->totalAmount(), 2);

        // Prefer the admin who reviewed this request; otherwise the signed-in / primary active admin.
        $preferredCoordinator = $inventoryRequest->reviewer;
        $coordinator = User::paymentOrderCoordinator(
            $preferredCoordinator instanceof User ? $preferredCoordinator : null
        );

        return [
            'inventoryRequest' => $inventoryRequest,
            'document' => [
                'serial_no' => $inventoryRequest->request_no,
                'form_date' => $formDate,
                'payee_name' => $inventoryRequest->clientDisplayName(),
                'client_id_number' => $inventoryRequest->client_id_number,
                'client_office' => $inventoryRequest->client_office,
                'amount_figures' => $grandTotal,
                'amount_words' => AmountInWords::peso($grandTotal),
                'payment_for' => $inventoryRequest->purpose,
                'detail_lines' => $detailLines,
                'detail_total' => $grandTotal,
                'release' => [
                    'date_released' => $releasedAt,
                    'or_number' => $inventoryRequest->or_number,
                    'released_by' => $coordinator['name'],
                    'released_by_title' => $coordinator['title'],
                    'amount_paid' => (float) $inventoryRequest->amount_paid,
                    'received_by' => $inventoryRequest->clientDisplayName(),
                ],
                'coordinator' => $coordinator,
                'request_no' => $inventoryRequest->request_no,
                'payment_type' => $inventoryRequest->payment_type,
                'payment_status' => $inventoryRequest->payment_status,
                'verified_url' => route('requests.release-slip', $inventoryRequest),
            ],
        ];
    }
}
