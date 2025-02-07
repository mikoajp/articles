<?php

use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::prefix('v1')->group(function () {
    });
});
