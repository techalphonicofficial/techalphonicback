<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

/*
| Versioned JSON API. Public endpoints are read-only and rate limited; anything private requires a
| Sanctum token issued to an admin user with the matching permission.
*/

// Site API for the Next.js frontend (frontend/). The Next server sends X-Frontend-Key to skip IP rate limits.
Route::prefix('v1/site')->name('api.site.')->middleware('throttle:site')->group(function () {
    Route::get('layout', [V1\SiteController::class, 'layout'])->name('layout');
    Route::get('resolve', [V1\SiteController::class, 'resolve'])->name('resolve');
    Route::get('paths', [V1\SiteController::class, 'paths'])->name('paths');
    Route::get('redirects', [V1\SiteController::class, 'redirects'])->name('redirects');
    Route::post('redirects/{id}/hit', [V1\SiteController::class, 'redirectHit'])->whereNumber('id')->name('redirects.hit');
});

Route::prefix('v1')->name('api.')->middleware('throttle:api')->group(function () {
    Route::get('services', [V1\ServiceController::class, 'index'])->name('services.index');
    Route::get('services/{slug}', [V1\ServiceController::class, 'show'])->name('services.show');
    Route::get('pages/{slug}', [V1\PageController::class, 'show'])->name('pages.show');
    Route::get('blogs', [V1\BlogController::class, 'index'])->name('blogs.index');
    Route::get('blogs/{slug}', [V1\BlogController::class, 'show'])->name('blogs.show');
    Route::get('search', V1\SearchController::class)->name('search');
    Route::post('leads', [V1\LeadController::class, 'store'])->middleware('throttle:leads')->name('leads.store');

    Route::middleware('auth:sanctum')->prefix('private')->name('private.')->group(function () {
        Route::get('leads', [V1\LeadController::class, 'index'])->name('leads.index');
    });
});
