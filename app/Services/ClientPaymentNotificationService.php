<?php

namespace App\Services;

use App\Mail\ClientPaymentDueReminder;
use App\Mail\ClientPaymentFullyPaid;
use App\Models\InventoryRequest;
use App\Models\RequestInstallment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ClientPaymentNotificationService
{
    public function clientEmail(InventoryRequest $request): ?string
    {
        $email = trim((string) $request->client_email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Days until due (0 = due today). Null when not in the reminder window or already paid.
     */
    public function daysUntilDue(RequestInstallment $installment, int $windowDays = 3): ?int
    {
        if ($installment->due_date === null || $installment->isPaid()) {
            return null;
        }

        $today = now()->startOfDay();
        $due = $installment->due_date->copy()->startOfDay();
        $days = (int) $today->diffInDays($due, false);

        if ($days < 0 || $days > $windowDays) {
            return null;
        }

        return $days;
    }

    public function alreadyRemindedToday(RequestInstallment $installment): bool
    {
        return $installment->due_reminder_sent_at !== null
            && $installment->due_reminder_sent_at->isSameDay(now());
    }

    public function sendDueReminder(RequestInstallment $installment, int $windowDays = 3): bool
    {
        $installment->loadMissing('inventoryRequest');
        $request = $installment->inventoryRequest;

        if (! $request instanceof InventoryRequest) {
            return false;
        }

        $email = $this->clientEmail($request);
        if ($email === null) {
            return false;
        }

        if ($request->payment_status === 'paid' || $installment->isPaid()) {
            return false;
        }

        $daysRemaining = $this->daysUntilDue($installment, $windowDays);
        if ($daysRemaining === null) {
            return false;
        }

        if ($this->alreadyRemindedToday($installment)) {
            return false;
        }

        try {
            Mail::to($email, $request->clientDisplayName())->send(
                new ClientPaymentDueReminder($request, $installment, $daysRemaining)
            );

            $installment->forceFill(['due_reminder_sent_at' => now()])->save();

            return true;
        } catch (Throwable $e) {
            Log::warning('Client due reminder failed', [
                'installment_id' => $installment->id,
                'request_no' => $request->request_no,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendFullyPaidNotice(InventoryRequest $request): bool
    {
        $email = $this->clientEmail($request);
        if ($email === null) {
            return false;
        }

        if ($request->payment_status !== 'paid') {
            return false;
        }

        if ($request->paid_notice_sent_at !== null) {
            return false;
        }

        try {
            Mail::to($email, $request->clientDisplayName())->send(
                new ClientPaymentFullyPaid($request)
            );

            $request->forceFill(['paid_notice_sent_at' => now()])->save();

            return true;
        } catch (Throwable $e) {
            Log::warning('Client fully-paid notice failed', [
                'request_no' => $request->request_no,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
