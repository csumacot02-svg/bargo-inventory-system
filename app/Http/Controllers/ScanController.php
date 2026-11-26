<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(): View
    {
        return view('scan.index');
    }

    public function lookup(Request $request): View|JsonResponse
    {
        $code = trim((string) $request->input('code', ''));

        if ($code === '') {
            if ($request->wantsJson()) {
                return response()->json(['found' => false, 'message' => 'Enter or scan a code.'], 422);
            }

            return back()->with('error', 'Enter or scan a code.');
        }

        $item = InventoryItem::query()
            ->with(['category', 'supplier'])
            ->where(function ($query) use ($code) {
                $query->where('qr_code', $code)
                    ->orWhere('barcode', $code)
                    ->orWhere('item_code', $code);
            })
            ->first();

        if ($request->user()) {
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'scan_lookup',
                'module' => 'scan',
                'description' => $item
                    ? 'Scanned item '.$item->item_code
                    : 'Scan miss for code '.$code,
                'ip_address' => $request->ip(),
            ]);
        }

        if ($request->wantsJson()) {
            if (! $item) {
                return response()->json(['found' => false, 'message' => 'No item matched that code.']);
            }

            return response()->json([
                'found' => true,
                'item' => [
                    'id' => $item->id,
                    'item_code' => $item->item_code,
                    'name' => $item->name,
                    'category' => $item->category?->name,
                    'quantity_on_hand' => $item->quantity_on_hand,
                    'unit' => $item->unit,
                    'condition' => $item->condition,
                    'location' => $item->location,
                    'stock_status' => $item->stockStatus(),
                    'url' => route('inventory.show', $item),
                ],
            ]);
        }

        if (! $item) {
            return redirect()->route('scan.index')->with('error', 'No inventory item matched “'.$code.'”.');
        }

        return view('scan.result', compact('item', 'code'));
    }
}
