<?php

namespace Modules\Articles\Services;

use Illuminate\Support\Facades\Log;
use Modules\Articles\Entities\Article;

class ArticleService
{
    public function createArticle(array $data): Article
    {
        return Article::create($data);
    }

    public function updateArticle(int $id, array $data): Article
    {
        $article = Article::findOrFail($id);
        $article->update($data);
        return $article->fresh();
    }

    public function deleteArticle(int $id): void
    {
        $article = Article::findOrFail($id);
        $article->delete();
    }

    public function getPaginatedArticles(int $page, int $perPage): array
    {
        try {
            $paginator = Article::orderByDesc('created_at')
                ->paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Pagination error: '.$e->getMessage());
            return [
                'data' => [],
                'meta' => []
            ];
        }
    }
}
