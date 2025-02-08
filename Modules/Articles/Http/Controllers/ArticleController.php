<?php

namespace Modules\Articles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Articles\Services\ArticleService;
use Modules\Articles\Services\ArticleCacheService;

class ArticleController extends Controller
{
    public function __construct(
        private ArticleCacheService $cacheService,
        private ArticleService $articleService
    ) {}

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

        $article = $this->articleService->createArticle($validated);
        return response()->json($article, 201);
    }

    public function show($id)
    {
        return response()->json(
            $this->cacheService->getArticle($id)
        );
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        $article = $this->articleService->updateArticle($id, $validated);
        return response()->json($article);
    }

    public function destroy($id)
    {
        $this->articleService->deleteArticle($id);
        return response()->json(null, 204);
    }
}
