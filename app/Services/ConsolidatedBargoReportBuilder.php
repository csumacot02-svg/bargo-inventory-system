<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryRequestItem;
use App\Models\User;

class ConsolidatedBargoReportBuilder
{
    /** @var list<string> */
    private array $months = [
        1 => 'JAN.',
        2 => 'FEB.',
        3 => 'MARCH',
        4 => 'APRIL',
        5 => 'MAY',
        6 => 'JUNE',
        7 => 'JULY',
        8 => 'AUG.',
        9 => 'SEPT.',
        10 => 'OCT.',
        11 => 'NOV.',
        12 => 'DEC.',
    ];

    public function build(int $year): array
    {
        $lines = InventoryRequestItem::query()
            ->whereHas('inventoryRequest', function ($query) use ($year) {
                $query->whereIn('status', ['released', 'completed'])
                    ->whereYear('released_at', $year);
            })
            ->with(['inventoryItem.category', 'inventoryRequest'])
            ->get();

        $catalog = InventoryItem::query()
            ->with('category')
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $soldIds = $lines->pluck('inventory_item_id')->unique()->filter();
        $missing = InventoryItem::query()
            ->with('category')
            ->whereIn('id', $soldIds->diff($catalog->keys()))
            ->get()
            ->keyBy('id');

        $items = $catalog->union($missing);

        $matrix = [];

        foreach ($items as $item) {
            $matrix[$item->id] = [
                'product' => $item->name,
                'category' => $item->category?->name,
                'months' => array_fill(1, 12, 0.0),
            ];
        }

        foreach ($lines as $line) {
            $item = $line->inventoryItem;
            $key = $line->inventory_item_id ?: ('missing-'.$line->id);

            if (! isset($matrix[$key])) {
                $matrix[$key] = [
                    'product' => $item?->name ?? 'Unknown item',
                    'category' => $item?->category?->name,
                    'months' => array_fill(1, 12, 0.0),
                ];
            }

            $month = (int) optional($line->inventoryRequest->released_at)->format('n');
            if ($month < 1 || $month > 12) {
                continue;
            }

            $qty = (float) ($line->quantity_approved ?? $line->quantity_requested);
            $price = (float) ($item?->unit_price ?? 0);
            $matrix[$key]['months'][$month] += $qty * $price;
        }

        $rows = [];
        $monthlyTotals = array_fill(1, 12, 0.0);
        $index = 1;

        foreach ($matrix as $entry) {
            $yearly = array_sum($entry['months']);
            foreach ($entry['months'] as $month => $amount) {
                $monthlyTotals[$month] += $amount;
            }

            $label = $entry['product'];
            if (filled($entry['category'])) {
                $label = $entry['category'].' — '.$entry['product'];
            }

            $rows[] = [
                'no' => $index++,
                'product' => $label,
                'months' => $entry['months'],
                'yearly_total' => $yearly,
            ];
        }

        $ranked = collect($rows)->sortByDesc('yearly_total')->values();
        $rankMap = [];
        $rank = 1;
        foreach ($ranked as $row) {
            $rankMap[$row['product']] = $row['yearly_total'] > 0 ? $rank : null;
            if ($row['yearly_total'] > 0) {
                $rank++;
            }
        }

        foreach ($rows as &$row) {
            $row['rank'] = $rankMap[$row['product']];
        }
        unset($row);

        $overall = array_sum($monthlyTotals);
        $highest = collect($rows)->sortByDesc('yearly_total')->first();

        return [
            'year' => $year,
            'month_labels' => $this->months,
            'rows' => $rows,
            'monthly_totals' => $monthlyTotals,
            'overall_total' => $overall,
            'highest_selling' => ($highest && $highest['yearly_total'] > 0) ? $highest['product'] : '—',
            'inventory_value' => InventoryItem::totalOnHandValue(),
            'signatories' => User::reportSignatories(),
            'generated_at' => now(),
        ];
    }
}
