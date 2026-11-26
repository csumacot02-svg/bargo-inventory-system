<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(): View
    {
        $lowStock = InventoryItem::query()
            ->with('category')
            ->where('is_active', true)
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->orderBy('quantity_on_hand')
            ->get();

        $outOfStock = $lowStock->where('quantity_on_hand', '<=', 0);

        $expiringSoon = InventoryItem::query()
            ->with('category')
            ->where('is_active', true)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', now()->addDays(30))
            ->whereDate('expiration_date', '>=', now())
            ->orderBy('expiration_date')
            ->get();

        $expired = InventoryItem::query()
            ->with('category')
            ->where('is_active', true)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<', now())
            ->orderBy('expiration_date')
            ->get();

        $damaged = InventoryItem::query()
            ->with('category')
            ->where('is_active', true)
            ->whereIn('condition', ['damaged', 'expired'])
            ->orderBy('name')
            ->get();

        $overdueCredits = InventoryRequest::query()
            ->with(['requester', 'items.inventoryItem', 'installments'])
            ->where('payment_type', 'credit')
            ->where('payment_status', '!=', 'paid')
            ->where(function ($q) {
                $q->whereHas('installments', function ($installment) {
                    $installment->whereIn('status', ['unpaid', 'partial'])
                        ->whereDate('due_date', '<', now());
                })->orWhere(function ($legacy) {
                    $legacy->whereDoesntHave('installments')
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now());
                });
            })
            ->orderBy('due_date')
            ->get();

        $overdueInstallments = \App\Models\RequestInstallment::query()
            ->with(['inventoryRequest.requester'])
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '<', now())
            ->whereHas('inventoryRequest', function ($q) {
                $q->where('payment_type', 'credit')
                    ->where('payment_status', '!=', 'paid');
            })
            ->orderBy('due_date')
            ->get();

        return view('alerts.index', compact(
            'lowStock',
            'outOfStock',
            'expiringSoon',
            'expired',
            'damaged',
            'overdueCredits',
            'overdueInstallments',
        ));
    }
}
