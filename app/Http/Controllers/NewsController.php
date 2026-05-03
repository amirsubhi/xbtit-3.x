<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        $news = News::with('author')->latest()->paginate(10);

        return view('news.index', compact('news'));
    }

    public function show(int $id): View
    {
        $article = News::with('author')->findOrFail($id);

        return view('news.show', compact('article'));
    }

    public function create(): View
    {
        return view('news.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body'  => ['required', 'string'],
        ]);

        $article = News::create([
            'title'   => $request->input('title'),
            'body'    => $request->input('body'),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('news.show', $article->id)
            ->with('status', 'News article published.');
    }

    public function edit(int $id): View
    {
        $article = News::findOrFail($id);

        return view('news.edit', compact('article'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $article = News::findOrFail($id);

        $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body'  => ['required', 'string'],
        ]);

        $article->update([
            'title' => $request->input('title'),
            'body'  => $request->input('body'),
        ]);

        return redirect()->route('news.show', $article->id)
            ->with('status', 'News article updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        News::findOrFail($id)->delete();

        return redirect()->route('news.index')
            ->with('status', 'News article deleted.');
    }
}
