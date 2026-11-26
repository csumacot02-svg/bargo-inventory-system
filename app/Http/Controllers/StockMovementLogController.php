<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockMovementLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageInventory(), 403);

        $movements = $this->filtered($request)
            ->with(['inventoryItem', 'user'])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $types = StockMovement::query()->distinct()->orderBy('type')->pluck('type');

        return view('logs.stock-movements', compact('movements', 'types'));
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->canManageInventory(), 403);

        $filename = 'bargo-stock-movements-'.now()->format('Ymd-His').'.csv';
        $rows = $this->filtered($request)->with(['inventoryItem', 'user'])->latest()->get();

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['When', 'Item', 'Type', 'Change', 'Before', 'After', 'User', 'Notes']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->created_at?->toDateTimeString(),
                    $row->inventoryItem?->name,
                    $row->type,
                    $row->quantity_change,
                    $row->quantity_before,
                    $row->quantity_after,
                    $row->user?->name,
                    $row->notes,
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function filtered(Request $request)
    {
        return StockMovement::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('notes', 'like', $term)
                        ->orWhere('type', 'like', $term)
                        ->orWhereHas('inventoryItem', fn ($item) => $item->where('name', 'like', $term)->orWhere('item_code', 'like', $term));
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')));
    }
}
