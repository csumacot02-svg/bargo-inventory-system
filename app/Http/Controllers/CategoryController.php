<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->canManageMasterData(), 403);

        return view('categories.index', [
            'categories' => Category::query()->withCount('items')->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageMasterData(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        Category::query()->create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'module' => 'categories',
            'description' => 'Created category '.$data['name'],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        abort_unless($request->user()->canManageMasterData(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id],
            'description' => ['nullable', 'string'],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'module' => 'categories',
            'description' => 'Updated category '.$category->name,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        abort_unless($request->user()->canManageMasterData(), 403);

        if ($category->items()->exists()) {
            return back()->with('error', 'Cannot delete a category that still has inventory items.');
        }

        $name = $category->name;
        $category->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'delete',
            'module' => 'categories',
            'description' => 'Deleted category '.$name,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Category removed.');
    }
}
