<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('sub', 0)->with('children')->orderBy('sort_index')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'sub'        => ['nullable', 'integer'],
            'sort_index' => ['integer', 'min:0'],
        ]);

        Category::create([
            'name'       => $data['name'],
            'sub'        => $data['sub'] ?: 0,
            'sort_index' => $data['sort_index'] ?? 0,
        ]);

        return back()->with('status', 'Category created.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return back()->with('status', 'Category deleted.');
    }
}
