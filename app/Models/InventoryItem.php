<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = [
        'item_code',
        'name',
        'category_id',
        'supplier_id',
        'description',
        'unit',
        'unit_price',
        'quantity_on_hand',
        'reorder_level',
        'condition',
        'expiration_date',
        'location',
        'barcode',
        'qr_code',
        'is_active',
        'is_perishable',
        'shelf_life_days',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'expiration_date' => 'date',
            'is_active' => 'boolean',
            'is_perishable' => 'boolean',
            'shelf_life_days' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(InventoryLot::class);
    }

    public function syncExpirationFromLots(): void
    {
        $earliest = $this->lots()
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiration_date')
            ->min('expiration_date');

        if ($earliest) {
            $this->update(['expiration_date' => $earliest]);
        }
    }

    public function isLowStock(): bool
    {
        return $this->quantity_on_hand <= $this->reorder_level;
    }

    public function isExpired(): bool
    {
        return $this->expiration_date !== null && $this->expiration_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->expiration_date === null || $this->isExpired()) {
            return false;
        }

        return $this->expiration_date->lte(now()->addDays($days));
    }

    /**
     * Usable stock for perishables = on-hand qty only while not expired.
     */
    public function usableQuantity(): float
    {
        if ($this->is_perishable && $this->isExpired()) {
            return 0.0;
        }

        return (float) $this->quantity_on_hand;
    }

    public function perishableStatus(): string
    {
        if (! $this->is_perishable) {
            return 'non-perishable';
        }

        if ($this->isExpired()) {
            return 'expired — not issuable';
        }

        if ($this->isExpiringSoon(7)) {
            return 'critical — expires within 7 days';
        }

        if ($this->isExpiringSoon(30)) {
            return 'expiring soon';
        }

        return 'fresh';
    }

    public function stockStatus(): string
    {
        if ($this->is_perishable && $this->isExpired()) {
            return 'expired';
        }

        if ($this->usableQuantity() <= 0) {
            return 'out';
        }

        if ($this->isLowStock()) {
            return 'low';
        }

        return 'ok';
    }

    public function onHandValue(): float
    {
        return round((float) $this->quantity_on_hand * (float) $this->unit_price, 2);
    }

    public static function totalOnHandValue(): float
    {
        return (float) static::query()
            ->selectRaw('COALESCE(SUM(quantity_on_hand * COALESCE(unit_price, 0)), 0) as total')
            ->value('total');
    }
}
