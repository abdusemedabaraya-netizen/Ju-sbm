<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    private const PRIORITIES = ['low', 'medium', 'high', 'critical'];

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100', 'unique:categories,name'],
            'default_priority' => ['required', Rule::in(self::PRIORITIES)],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $category = Category::create([
            'slug'             => $this->makeSlug($data['name']),
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

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'default_priority' => ['required', Rule::in(self::PRIORITIES)],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

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

    private function makeSlug(string $name): string
    {
        $slug = Str::slug($name, '_');

        if ($slug === '') {               // e.g. a name written only in Amharic script
            $slug = 'category_' . Str::lower(Str::random(6));
        }

        $base = $slug;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base . '_' . Str::lower(Str::random(4));
        }

        return Str::limit($slug, 50, '');
    }
}
