<?php

namespace App\Console\Commands;

use App\Mail\DailyInventoryDigest;
use App\Models\InventoryItem;
use App\Models\InventoryRequest;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyInventoryDigest extends Command
{
    protected $signature = 'bargo:send-daily-digest';

    protected $description = 'Email administrators pending users, low stock, expiring items, and overdue credit installments.';

    public function handle(): int
    {
        $admins = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_ACTIVE)
            ->get();

        if ($admins->isEmpty()) {
            $this->warn('No active administrators to notify.');

            return self::SUCCESS;
        }

        $payload = [
            'date' => now()->toDateString(),
            'pending_users' => User::query()->where('status', User::STATUS_PENDING)->count(),
            'low_stock' => InventoryItem::query()
                ->where('is_active', true)
                ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
                ->count(),
            'expiring_7' => InventoryItem::query()
                ->where('is_active', true)
                ->whereNotNull('expiration_date')
                ->whereDate('expiration_date', '<=', now()->addDays(7))
                ->whereDate('expiration_date', '>=', now())
                ->count(),
            'overdue_credit' => \App\Models\RequestInstallment::query()
                ->whereIn('status', ['unpaid', 'partial'])
                ->whereDate('due_date', '<', now())
                ->whereHas('inventoryRequest', function ($q) {
                    $q->where('payment_type', 'credit')
                        ->where('payment_status', '!=', 'paid');
                })
                ->count(),
        ];

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new DailyInventoryDigest($payload));
        }

        $this->info('Daily digest queued/sent to '.$admins->count().' administrator(s).');

        return self::SUCCESS;
    }
}
