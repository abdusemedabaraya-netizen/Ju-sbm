<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();
        $usage      = DB::table('reports')->selectRaw('category, COUNT(*) as c')->groupBy('category')->pluck('c', 'category');

        return view('categories.index', compact('categories', 'usage'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();

        $category = Category::create([
            'slug'             => Category::generateUniqueSlug($data['name']),
            'name'             => $data['name'],
            'default_priority' => $data['default_priority'],
            'sort_order'       => $data['sort_order'] ?? 0,
            'is_active'        => $request->boolean('is_active'),
        ]);

        AuditLogger::log('category.created', $category, $category->name);

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        // The slug never changes: existing reports keep pointing at it.
        $category->update([
            'name'             => $data['name'],
            'default_priority' => $data['default_priority'],
            'sort_order'       => $data['sort_order'] ?? 0,
            'is_active'        => $request->boolean('is_active'),
        ]);

        AuditLogger::log('category.updated', $category, $category->name);

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if (DB::table('reports')->where('category', $category->slug)->exists()) {
            return back()->with('error', 'Reports use this category, so it cannot be deleted. Deactivate it instead.');
        }

        AuditLogger::log('category.deleted', $category, $category->name);
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
