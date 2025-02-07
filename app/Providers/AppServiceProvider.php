<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Articles\Entities\Article;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Article::observe(\App\Observers\ArticleObserver::class);
    }
}
