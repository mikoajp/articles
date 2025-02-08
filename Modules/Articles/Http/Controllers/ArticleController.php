<?php

namespace Modules\Articles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Articles\Entities\Article;
use Modules\Articles\Services\ArticleCacheService;

class ArticleController extends Controller
{
    private $cacheService;

    public function __construct(ArticleCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(Request $request)
    {
        $perPage = max(1, min(100, $request->input('per_page', 15)));
        $page = max(1, $request->input('page', 1));

        return response()->json(
            $this->cacheService->getArticles($page, $perPage)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        $article = Article::create($validated);
        return response()->json($article, 201);
    }

    public function show($id)
    {
        $article = $this->cacheService->getArticle($id);
        return response()->json($article);
    }

    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        $article->update($validated);
        return response()->json($article);
    }

    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        $article->delete();
        return response()->json(null, 204);
    }
}
