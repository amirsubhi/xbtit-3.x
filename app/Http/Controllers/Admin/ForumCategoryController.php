<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumCategory;
use Illuminate\Http\Request;

class ForumCategoryController extends Controller
{
    public function index()
    {
        $categories = ForumCategory::with('forums')->orderBy('display_order')->get();
        return view('admin.forum.index', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'display_order' => ['integer', 'min:0'],
        ]);
        ForumCategory::create($data);

        return back()->with('status', 'Category created.');
    }

    public function destroyCategory(ForumCategory $category)
    {
        $category->delete();
        return back()->with('status', 'Category deleted.');
    }

    public function storeForum(Request $request, ForumCategory $category)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:300'],
            'display_order' => ['integer', 'min:0'],
        ]);

        $category->forums()->create($data);

        return back()->with('status', 'Forum created.');
    }

    public function destroyForum(Forum $forum)
    {
        $forum->delete();
        return back()->with('status', 'Forum deleted.');
    }
}
