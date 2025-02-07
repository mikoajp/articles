<?php

namespace App\Observers;

use Modules\Articles\Entities\Article;
use Modules\Articles\Services\ArticleCacheService;

class ArticleObserver
{
    public function saved(Article $article)
    {
        $service = app(ArticleCacheService::class);
        $service->clearIndexCache();
        $service->putArticle($article);
    }

    public function deleted(Article $article)
    {
        $service = app(ArticleCacheService::class);
        $service->clearIndexCache();
        $service->forgetArticle($article->id);
    }
}
