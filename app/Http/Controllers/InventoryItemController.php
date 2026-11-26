<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Services\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function __construct(private InventoryStockService $stockService) {}

    public function index(Request $request): View
    {
        $items = InventoryItem::query()
            ->with(['category', 'supplier'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('item_code', 'like', $term)
                        ->orWhere('barcode', 'like', $term);
                });
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->string('status') === 'low') {
                    $query->whereColumn('quantity_on_hand', '<=', 'reorder_level');
                }
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get();

        return view('inventory.index', compact('items', 'categories'));
    }

    public function create(): View
    {
        return view('inventory.create', [
            'categories' => Category::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $openingQty = (float) $data['quantity_on_hand'];
        $data['quantity_on_hand'] = 0;
        $data['item_code'] = $data['item_code'] ?: $this->generateItemCode();
        $data['qr_code'] = $data['qr_code'] ?: 'QR-'.$data['item_code'];

        $item = InventoryItem::create($data);

        if ($openingQty > 0) {
            $this->stockService->adjust(
                itemId: $item->id,
                change: $openingQty,
                type: 'opening_balance',
                actor: $request->user(),
                referenceType: InventoryItem::class,
                referenceId: $item->id,
                notes: 'Opening balance',
                expirationDate: $item->expiration_date?->toDateString(),
                lotSource: 'opening_balance',
            );
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'module' => 'inventory',
            'description' => 'Added inventory item '.$item->item_code.' — '.$item->name,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('inventory.index')->with('success', 'Inventory item saved. Opening quantity was posted to the stock ledger.');
    }

    public function show(InventoryItem $inventory): View
    {
        $inventory->load(['category', 'supplier']);

        $movements = $inventory->movements()
            ->with('user')
            ->latest()
            ->paginate(15);

        $lots = $inventory->lots()
            ->where('quantity_remaining', '>', 0)
            ->orderByRaw('expiration_date is null')
            ->orderBy('expiration_date')
            ->get();

        return view('inventory.show', [
            'item' => $inventory,
            'movements' => $movements,
            'lots' => $lots,
        ]);
    }

    public function edit(InventoryItem $inventory): View
    {
        return view('inventory.edit', [
            'item' => $inventory,
            'categories' => Category::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, InventoryItem $inventory): RedirectResponse
    {
        $data = $this->validated($request, $inventory->id, editing: true);
        $inventory->update($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'module' => 'inventory',
            'description' => 'Updated inventory item '.$inventory->item_code,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('inventory.show', $inventory)->with('success', 'Inventory item updated. Quantity changes must use Stock Adjustment.');
    }

    public function destroy(Request $request, InventoryItem $inventory): RedirectResponse
    {
        abort_unless($request->user()->canDeleteInventory(), 403);

        $code = $inventory->item_code;
        $inventory->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'delete',
            'module' => 'inventory',
            'description' => 'Deleted inventory item '.$code,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('inventory.index')->with('success', 'Inventory item removed.');
    }

    private function validated(Request $request, ?int $ignoreId = null, bool $editing = false): array
    {
        $isPerishable = $request->boolean('is_perishable');

        $rules = [
            'item_code' => ['nullable', 'string', 'max:50', 'unique:inventory_items,item_code,'.($ignoreId ?? 'NULL')],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'description' => ['nullable', 'string'],
            'unit' => ['required', 'string', 'max:30'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'condition' => ['required', 'in:good,fair,damaged,expired'],
            'expiration_date' => [$isPerishable ? 'required' : 'nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:inventory_items,barcode,'.($ignoreId ?? 'NULL')],
            'qr_code' => ['nullable', 'string', 'max:100', 'unique:inventory_items,qr_code,'.($ignoreId ?? 'NULL')],
            'shelf_life_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'is_perishable' => ['sometimes', 'boolean'],
        ];

        if (! $editing) {
            $rules['quantity_on_hand'] = ['required', 'numeric', 'min:0'];
        }

        $data = $request->validate($rules);

        unset($data['quantity_on_hand']);

        if (! $editing) {
            $data['quantity_on_hand'] = (float) $request->input('quantity_on_hand', 0);
        }

        return $data + [
            'is_active' => $request->boolean('is_active', true),
            'is_perishable' => $isPerishable,
            'unit_price' => $data['unit_price'] ?? 0,
        ];
    }

    private function generateItemCode(): string
    {
        do {
            $code = 'INV-'.strtoupper(Str::random(6));
        } while (InventoryItem::query()->where('item_code', $code)->exists());

        return $code;
    }
}
