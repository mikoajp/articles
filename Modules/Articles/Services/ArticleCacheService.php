<?php

namespace Modules\Articles\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Modules\Articles\Entities\Article;
class ArticleCacheService
{
    public function __construct(
        private ArticleService $articleService
    ) {}
    private const CACHE_TTL = 60;
    private const CACHE_KEY_PREFIX = 'articles:list:';
    private const CACHE_KEY_PREFIX_SINGLE = 'article:';
    private const CACHE_INDEX_VERSION_KEY = 'articles:index_version';

    public function getArticles(int $page, int $perPage): array
    {
        try {
            return Cache::remember(
                $this->getIndexKey($page, $perPage),
                self::CACHE_TTL,
                function() use ($page, $perPage) {
                    return $this->articleService->getPaginatedArticles($page, $perPage);
                }
            );
        } catch (\Exception $e) {
           Log::error('Cache error: '.$e->getMessage());
            return $this->articleService->getPaginatedArticles($page, $perPage);
        }
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


    private function getSingleKey(int $id): string
    {
        return self::CACHE_KEY_PREFIX_SINGLE . $id;
    }

    private function getIndexKey(int $page, int $perPage): string
    {
        return self::CACHE_KEY_PREFIX . "page_{$page}_per_{$perPage}";
    }

    public function clearCache(?int $id = null)
    {
        $this->clearIndexCache();
        if ($id) {
            $this->forgetArticle($id);
        }
    }
}
