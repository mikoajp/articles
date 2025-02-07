<?php

namespace Modules\Articles\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Articles\Entities\Article;

class ArticleCacheService
{
    private const CACHE_TTL = 60;
    private const CACHE_KEY_PREFIX_INDEX = 'articles:index:';
    private const CACHE_KEY_PREFIX_SINGLE = 'article:';

    public function getArticles(int $perPage, int $page)
    {
        return Cache::remember(
            $this->getIndexKey($page, $perPage),
            self::CACHE_TTL,
            function () use ($perPage) {
                return Article::orderByDesc('created_at')
                    ->select(['id', 'title', 'content', 'created_at', 'updated_at'])
                    ->paginate($perPage);
            }
        );
    }

    public function getArticle(int $id)
    {
        return Cache::remember(
            $this->getSingleKey($id),
            self::CACHE_TTL,
            function () use ($id) {
                return Article::select([
                    'id', 'title', 'content', 'created_at', 'updated_at'
                ])->findOrFail($id);
            }
        );
    }

    public function putArticle(Article $article)
    {
        Cache::put(
            $this->getSingleKey($article->id),
            $article,
            self::CACHE_TTL
        );
    }

    public function clearCache(?int $id = null)
    {
        Cache::delete(self::CACHE_KEY_PREFIX_INDEX . '*');
        if ($id) {
            Cache::delete($this->getSingleKey($id));
        }
    }

    private function getIndexKey(int $page, int $perPage): string
    {
        return self::CACHE_KEY_PREFIX_INDEX . "page_{$page}_per_{$perPage}";
    }

    private function getSingleKey(int $id): string
    {
        return self::CACHE_KEY_PREFIX_SINGLE . $id;
    }
}
