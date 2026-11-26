<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_RELEASED = 'released';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    /** @return list<string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_RELEASED,
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ];
    }

    /** Active (non-history) statuses. */
    public static function activeStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_RELEASED,
        ];
    }

    /** Finalized / archive statuses for Request History. */
    public static function historyStatuses(): array
    {
        return [
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ];
    }

    protected $fillable = [
        'request_no',
        'requester_id',
        'reviewed_by',
        'status',
        'purpose',
        'client_name',
        'client_id_number',
        'client_office',
        'client_email',
        'payment_type',
        'payment_status',
        'due_date',
        'installment_frequency',
        'installment_count',
        'installment_start_date',
        'remarks',
        'reviewed_at',
        'released_at',
        'completed_at',
        'completed_by',
        'cancelled_at',
        'cancelled_by',
        'stock_deducted',
        'paid_at',
        'paid_notice_sent_at',
        'or_number',
        'amount_paid',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'installment_start_date' => 'date',
            'reviewed_at' => 'datetime',
            'released_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
            'paid_notice_sent_at' => 'datetime',
            'stock_deducted' => 'boolean',
            'amount_paid' => 'decimal:2',
            'installment_count' => 'integer',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryRequestItem::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(RequestInstallment::class)->orderBy('sequence');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RequestPayment::class)->latest('paid_at');
    }

    public function needsPaymentConfirmation(): bool
    {
        return in_array($this->payment_type, ['cash', 'credit'], true)
            && $this->payment_status !== 'paid'
            && in_array($this->status, [self::STATUS_APPROVED, self::STATUS_RELEASED], true);
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, self::historyStatuses(), true);
    }

    public function isReadOnly(): bool
    {
        return $this->isFinalized();
    }

    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_RELEASED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_RELEASED => 'Released',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'badge-status-pending',
            self::STATUS_APPROVED => 'badge-status-approved',
            self::STATUS_RELEASED => 'badge-status-released',
            self::STATUS_COMPLETED => 'badge-status-completed',
            self::STATUS_REJECTED => 'badge-status-rejected',
            self::STATUS_CANCELLED => 'badge-status-cancelled',
            default => 'badge-pending',
        };
    }

    public function itemsSummary(int $limit = 3): string
    {
        $names = $this->items
            ->map(fn (InventoryRequestItem $line) => $line->inventoryItem?->name)
            ->filter()
            ->values();

        if ($names->isEmpty()) {
            return '—';
        }

        $shown = $names->take($limit)->implode(', ');
        $extra = $names->count() - $limit;

        return $extra > 0 ? $shown.' (+'.$extra.' more)' : $shown;
    }

    public function hasInstallmentPlan(): bool
    {
        return $this->payment_type === 'credit'
            && in_array($this->installment_frequency, ['daily', 'monthly'], true)
            && (int) $this->installment_count > 1;
    }

    public function installmentPlanLabel(): ?string
    {
        if ($this->payment_type !== 'credit') {
            return null;
        }

        $frequency = $this->installment_frequency ?: 'once';

        if ($frequency === 'once' || (int) $this->installment_count <= 1) {
            return 'Single payment'.($this->due_date ? ' due '.$this->due_date->format('M d, Y') : '');
        }

        return ucfirst($frequency).' × '.(int) $this->installment_count.' installments';
    }

    public function isOverdue(): bool
    {
        if ($this->payment_type !== 'credit' || $this->payment_status === 'paid') {
            return false;
        }

        if ($this->relationLoaded('installments') ? $this->installments->isNotEmpty() : $this->installments()->exists()) {
            return $this->installments
                ->contains(fn (RequestInstallment $row) => $row->isOverdue());
        }

        return $this->due_date !== null && $this->due_date->isPast();
    }

    public function nextInstallmentDue(): ?RequestInstallment
    {
        return $this->installments
            ->first(fn (RequestInstallment $row) => ! $row->isPaid());
    }

    public function clientDisplayName(): string
    {
        $name = trim((string) $this->client_name);

        return $name !== '' ? $name : (string) ($this->requester?->name ?? '—');
    }

    public function totalAmount(): float
    {
        return (float) $this->items->sum(function (InventoryRequestItem $line) {
            $qty = (float) ($line->quantity_approved ?? $line->quantity_requested);
            $price = (float) ($line->inventoryItem?->unit_price ?? 0);

            return $qty * $price;
        });
    }

    public function balanceDue(): float
    {
        return max(0, round($this->totalAmount() - (float) $this->amount_paid, 2));
    }

    public static function nextOrNumber(): string
    {
        $prefix = 'OR-'.now()->format('Y').'-';

        $lastRequest = static::query()
            ->where('or_number', 'like', $prefix.'%')
            ->orderByDesc('or_number')
            ->value('or_number');

        $lastPayment = RequestPayment::query()
            ->where('or_number', 'like', $prefix.'%')
            ->orderByDesc('or_number')
            ->value('or_number');

        $last = collect([$lastRequest, $lastPayment])->filter()->sort()->last();
        $seq = $last ? ((int) substr((string) $last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    public static function releasedSalesTotal($from = null, $to = null): float
    {
        $query = InventoryRequestItem::query()
            ->join('inventory_requests', 'inventory_requests.id', '=', 'inventory_request_items.inventory_request_id')
            ->leftJoin('inventory_items', 'inventory_items.id', '=', 'inventory_request_items.inventory_item_id')
            ->whereIn('inventory_requests.status', [self::STATUS_RELEASED, self::STATUS_COMPLETED]);

        if ($from) {
            $query->whereDate('inventory_requests.released_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('inventory_requests.released_at', '<=', $to);
        }

        return (float) $query
            ->selectRaw('COALESCE(SUM(COALESCE(inventory_request_items.quantity_approved, inventory_request_items.quantity_requested) * COALESCE(inventory_items.unit_price, 0)), 0) as total')
            ->value('total');
    }
}
