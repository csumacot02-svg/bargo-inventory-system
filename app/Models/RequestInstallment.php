<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestInstallment extends Model
{
    protected $fillable = [
        'inventory_request_id',
        'sequence',
        'due_date',
        'amount_due',
        'amount_paid',
        'status',
        'paid_at',
        'due_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'paid_at' => 'datetime',
            'due_reminder_sent_at' => 'datetime',
        ];
    }

    public function inventoryRequest(): BelongsTo
    {
        return $this->belongsTo(InventoryRequest::class);
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->amount_due - (float) $this->amount_paid, 2));
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->balanceDue() <= 0.009;
    }

    public function isOverdue(): bool
    {
        return ! $this->isPaid()
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    public function refreshStatus(): void
    {
        $balance = $this->balanceDue();

        if ($balance <= 0.009) {
            $this->forceFill([
                'amount_paid' => $this->amount_due,
                'status' => 'paid',
                'paid_at' => $this->paid_at ?? now(),
            ])->save();

            return;
        }

        $this->forceFill([
            'status' => (float) $this->amount_paid > 0 ? 'partial' : 'unpaid',
            'paid_at' => null,
        ])->save();
    }
}
