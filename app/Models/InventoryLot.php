<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLot extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'stock_in_id',
        'quantity_received',
        'quantity_remaining',
        'expiration_date',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:2',
            'quantity_remaining' => 'decimal:2',
            'expiration_date' => 'date',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }
}
