<?php

namespace Modules\Articles\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Articles\Entities\Article;

class ArticleCacheService
{
    private const CACHE_TTL = 60;
    private const CACHE_KEY_PREFIX_INDEX = 'articles:index:';
    private const CACHE_KEY_PREFIX_SINGLE = 'article:';
    private const CACHE_INDEX_VERSION_KEY = 'articles:index_version';

    public function getArticles(int $perPage, int $page)
    {
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);

        return Cache::remember(
            $this->getIndexKey($page, $perPage),
            self::CACHE_TTL,
            function () use ($perPage, $page) {
                return Article::orderByDesc('created_at')
                    ->select(['id', 'title', 'content', 'created_at', 'updated_at'])
                    ->paginate($perPage, ['*'], 'page', $page);
            }
        );
    }

    public function getArticle(int $id)
    {
        try {
            return Cache::remember(
                $this->getSingleKey($id),
                self::CACHE_TTL,
                function () use ($id) {
                    $article = Article::select([
                        'id', 'title', 'content', 'created_at', 'updated_at'
                    ])->findOrFail($id);

                    $this->putArticle($article);
                    return $article;
                }
            );
        } catch (ModelNotFoundException $e) {
            $this->forgetArticle($id);
            throw $e;
        }
    }

    public function putArticle(Article $article)
    {
        Cache::put(
            $this->getSingleKey($article->id),
            $article,
            self::CACHE_TTL
        );
    }

    public function forgetArticle(int $id)
    {
        Cache::forget($this->getSingleKey($id));
    }

    public function clearIndexCache()
    {
        Cache::increment(self::CACHE_INDEX_VERSION_KEY);
    }

    private function getIndexKey(int $page, int $perPage): string
    {
        return self::CACHE_KEY_PREFIX_INDEX .
            "v{$this->getIndexVersion()}:" .
            "page_{$page}_per_{$perPage}";
    }

    private function getSingleKey(int $id): string
    {
        return self::CACHE_KEY_PREFIX_SINGLE . $id;
    }

    private function getIndexVersion(): int
    {
        return (int) Cache::get(self::CACHE_INDEX_VERSION_KEY, 1);
    }

    public function clearCache(?int $id = null)
    {
        $this->clearIndexCache();
        if ($id) {
            $this->forgetArticle($id);
        }
    }
}
