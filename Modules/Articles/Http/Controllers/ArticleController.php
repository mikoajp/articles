<?php

namespace Modules\Articles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controller;
use Modules\Articles\Entities\Article;

class ArticleController extends Controller
{
    private const CACHE_TTL = 60;
    private const CACHE_KEY_PREFIX_INDEX = 'articles:index:';
    private const CACHE_KEY_PREFIX_SINGLE = 'article:';

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $articles = Cache::remember(
            self::CACHE_KEY_PREFIX_INDEX . "page_{$page}_per_{$perPage}",
            self::CACHE_TTL,
            function () use ($perPage) {
                return Article::orderByDesc('created_at')
                    ->select(['id', 'title', 'content', 'created_at', 'updated_at'])
                    ->paginate($perPage);
            }
        );

        return response()->json([
            'data' => $articles->items(),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
                'last_page' => $articles->lastPage(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        $article = Article::create($validated);

        $this->clearCache($article->id);

        return response()->json($article, 201);
    }

    public function show($id)
    {
        $article = Cache::remember(
            self::CACHE_KEY_PREFIX_SINGLE . $id,
            self::CACHE_TTL,
            function () use ($id) {
                return Article::select([
                    'id',
                    'title',
                    'content',
                    'created_at',
                    'updated_at'
                ])->findOrFail($id);
            }
        );

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

        $this->clearCache($id);
        Cache::put(
            self::CACHE_KEY_PREFIX_SINGLE . $id,
            $article->fresh(),
            self::CACHE_TTL
        );

        return response()->json($article);
    }

    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        $article->delete();

        $this->clearCache($id);

        return response()->json(null, 204);
    }

    private function clearCache($id = null)
    {
        Cache::delete(self::CACHE_KEY_PREFIX_INDEX . '*');
        if ($id) {
            Cache::delete(self::CACHE_KEY_PREFIX_SINGLE . $id);
        }
    }
}
