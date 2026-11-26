<?php

namespace App\Console\Commands;

use App\Models\RequestInstallment;
use App\Services\ClientPaymentNotificationService;
use Illuminate\Console\Command;

class SendClientPaymentReminders extends Command
{
    protected $signature = 'bargo:send-client-payment-reminders
                            {--days=3 : Start reminders this many days before due (daily through due date)}';

    protected $description = 'Email clients daily from 3 days before due through the due date.';

    public function handle(ClientPaymentNotificationService $notifier): int
    {
        $days = max(0, (int) $this->option('days'));
        $until = now()->startOfDay()->addDays($days);

        $installments = RequestInstallment::query()
            ->with('inventoryRequest')
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '>=', now()->toDateString())
            ->whereDate('due_date', '<=', $until->toDateString())
            ->where(function ($q) {
                $q->whereNull('due_reminder_sent_at')
                    ->orWhereDate('due_reminder_sent_at', '<', now()->toDateString());
            })
            ->whereHas('inventoryRequest', function ($q) {
                $q->where('payment_type', 'credit')
                    ->where('payment_status', '!=', 'paid')
                    ->whereNotNull('client_email');
            })
            ->orderBy('due_date')
            ->get();

        $sent = 0;

        foreach ($installments as $installment) {
            if ($notifier->sendDueReminder($installment, $days)) {
                $sent++;
                $remaining = $notifier->daysUntilDue($installment, $days);
                $this->line(sprintf(
                    'Reminded %s — %s #%d (%s)',
                    $installment->inventoryRequest?->client_email,
                    $installment->inventoryRequest?->request_no,
                    $installment->sequence,
                    $remaining === 0 ? 'due today' : "{$remaining} day(s) left"
                ));
            }
        }

        $this->info("Sent {$sent} client payment reminder(s).");

        return self::SUCCESS;
    }
}
