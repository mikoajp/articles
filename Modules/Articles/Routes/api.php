<?php

use Illuminate\Support\Facades\Route;
use Modules\Articles\Http\Controllers\ArticleController;

Route::prefix('api')->group(function() {
    Route::prefix('articles')->group(function() {
        Route::get('/', [ArticleController::class, 'index']);
        Route::post('/', [ArticleController::class, 'store']);
        Route::get('/{id}', [ArticleController::class, 'show']);
        Route::put('/{id}', [ArticleController::class, 'update']);
        Route::delete('/{id}', [ArticleController::class, 'destroy']);
    });
});
