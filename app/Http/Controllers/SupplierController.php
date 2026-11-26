<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->canManageMasterData(), 403);

        return view('suppliers.index', [
            'suppliers' => Supplier::query()->withCount('items')->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageMasterData(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        Supplier::query()->create($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'module' => 'suppliers',
            'description' => 'Created supplier '.$data['name'],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Supplier added.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        abort_unless($request->user()->canManageMasterData(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        $supplier->update($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'module' => 'suppliers',
            'description' => 'Updated supplier '.$supplier->name,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Supplier updated.');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        abort_unless($request->user()->canManageMasterData(), 403);

        if ($supplier->items()->exists() || $supplier->stockIns()->exists()) {
            return back()->with('error', 'Cannot delete a supplier that is used on items or stock-in records.');
        }

        $name = $supplier->name;
        $supplier->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'delete',
            'module' => 'suppliers',
            'description' => 'Deleted supplier '.$name,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Supplier removed.');
    }
}
