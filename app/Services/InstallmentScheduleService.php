<?php

namespace App\Services;

use App\Models\InventoryRequest;
use App\Models\RequestInstallment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InstallmentScheduleService
{
    /**
     * Build or rebuild installment rows from the request total and plan settings.
     * Existing paid amounts are preserved when regenerating (matched by sequence).
     */
    public function syncSchedule(InventoryRequest $request): void
    {
        if ($request->payment_type !== 'credit') {
            $request->installments()->delete();

            return;
        }

        $total = round($request->totalAmount(), 2);
        if ($total <= 0) {
            return;
        }

        $frequency = $request->installment_frequency ?: 'once';
        $count = max(1, (int) ($request->installment_count ?: 1));

        if ($frequency === 'once') {
            $count = 1;
        }

        $start = $request->installment_start_date
            ? Carbon::parse($request->installment_start_date)->startOfDay()
            : ($request->due_date
                ? Carbon::parse($request->due_date)->startOfDay()
                : now()->startOfDay());

        $amounts = $this->splitAmount($total, $count);
        $previousPaid = $request->installments()
            ->get()
            ->keyBy('sequence')
            ->map(fn (RequestInstallment $row) => (float) $row->amount_paid);

        DB::transaction(function () use ($request, $frequency, $count, $start, $amounts, $previousPaid) {
            $request->installments()->delete();

            $lastDue = null;

            for ($i = 1; $i <= $count; $i++) {
                $due = match ($frequency) {
                    'daily' => $start->copy()->addDays($i - 1),
                    'monthly' => $start->copy()->addMonthsNoOverflow($i - 1),
                    default => $request->due_date
                        ? Carbon::parse($request->due_date)->startOfDay()
                        : $start->copy(),
                };

                $paid = min($amounts[$i - 1], (float) ($previousPaid[$i] ?? 0));

                $installment = $request->installments()->create([
                    'sequence' => $i,
                    'due_date' => $due->toDateString(),
                    'amount_due' => $amounts[$i - 1],
                    'amount_paid' => $paid,
                    'status' => 'unpaid',
                ]);

                $installment->refreshStatus();
                $lastDue = $due;
            }

            if ($lastDue) {
                $request->forceFill(['due_date' => $lastDue->toDateString()])->save();
            }
        });
    }

    /**
     * Apply a payment amount across installments earliest-first (waterfall).
     */
    public function applyPayment(InventoryRequest $request, float $amount): void
    {
        $remaining = round($amount, 2);

        $installments = $request->installments()
            ->orderBy('sequence')
            ->lockForUpdate()
            ->get()
            ->filter(fn (RequestInstallment $row) => $row->balanceDue() > 0.009);

        foreach ($installments as $installment) {
            if ($remaining <= 0.009) {
                break;
            }

            $need = $installment->balanceDue();
            $pay = min($need, $remaining);

            $installment->forceFill([
                'amount_paid' => round((float) $installment->amount_paid + $pay, 2),
            ])->save();
            $installment->refreshStatus();

            $remaining = round($remaining - $pay, 2);
        }
    }

    /**
     * @return list<float>
     */
    private function splitAmount(float $total, int $count): array
    {
        $count = max(1, $count);
        $base = floor(($total / $count) * 100) / 100;
        $amounts = array_fill(0, $count, $base);
        $allocated = round($base * $count, 2);
        $remainder = round($total - $allocated, 2);
        $amounts[$count - 1] = round($amounts[$count - 1] + $remainder, 2);

        return $amounts;
    }
}
