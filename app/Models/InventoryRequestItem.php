<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRequestItem extends Model
{
    protected $fillable = [
        'inventory_request_id',
        'inventory_item_id',
        'quantity_requested',
        'quantity_approved',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:2',
            'quantity_approved' => 'decimal:2',
        ];
    }

    public function inventoryRequest(): BelongsTo
    {
        return $this->belongsTo(InventoryRequest::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
