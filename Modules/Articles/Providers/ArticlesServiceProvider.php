<?php

namespace Modules\Articles\Providers;

use Illuminate\Support\ServiceProvider;

class ArticlesServiceProvider extends ServiceProvider
{
    protected $moduleName = 'Articles';

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot()
    {
        $this->loadMigrationsFrom(module_path('Articles', 'Database/Migrations'));
        $this->loadRoutesFrom(module_path('Articles', 'Routes/web.php'));
        $this->loadRoutesFrom(module_path('Articles', 'Routes/api.php'));
    }
}
