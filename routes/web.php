<?php

use App\Http\Controllers\FrontendRedirectController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
| Laravel serves the admin panel, the APIs, sitemaps and robots.txt. The public website is the Next.js app in
| frontend/ (it proxies /admin, /api, /storage, /build, sitemaps and robots.txt here). Admin routes live in
| routes/admin.php; they are registered first so the /{slug} route below never shadows them.
*/
require __DIR__.'/admin.php';

// Old shortcut: commands now run from Admin → Maintenance ("Create storage link"), which needs a login,
// the system.maintenance permission and a POST — a public GET would let anyone run artisan on the server.
Route::get('simlink', fn () => redirect()->route('admin.maintenance.index'));

Route::get('robots.txt', RobotsController::class)->name('robots');
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('sitemap-{section}.xml', [SitemapController::class, 'section'])->where('section', '[a-z]+')->name('sitemap.section');

// Public website URLs. Kept as named routes because links, breadcrumbs, schema and sitemaps are generated from
// them; a request that reaches Laravel is 301'd to the same path on FRONTEND_URL.
Route::get('/', FrontendRedirectController::class)->name('home');
Route::get('search', FrontendRedirectController::class)->name('search');

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', FrontendRedirectController::class)->name('index');
    // ":slug" makes route('blog.category', $category) build the URL from the slug.
    Route::get('category/{category:slug}', FrontendRedirectController::class)->name('category');
    Route::get('tag/{tag:slug}', FrontendRedirectController::class)->name('tag');
    Route::get('{slug}', FrontendRedirectController::class)->where('slug', '[a-z0-9\-]+')->name('show');
});

Route::get('portfolio', FrontendRedirectController::class)->name('portfolio.index');
Route::get('portfolio/{slug}', FrontendRedirectController::class)->where('slug', '[a-z0-9\-]+')->name('portfolio.show');

Route::get('case-studies', FrontendRedirectController::class)->name('case-studies.index');
Route::get('case-studies/{slug}', FrontendRedirectController::class)->where('slug', '[a-z0-9\-]+')->name('case-studies.show');

Route::get('industries', FrontendRedirectController::class)->name('industries.index');

// Admin "Save & preview" links land here and open the draft on the Next.js preview route.
Route::get('preview/{type}/{token}', FrontendRedirectController::class)
    ->where(['type' => '[a-z_]+', 'token' => '[A-Za-z0-9]{48}'])
    ->name('preview');

Route::get('{slug}', FrontendRedirectController::class)->where('slug', '[a-z0-9\-]+')->name('slug');

// Unmatched multi-segment URLs (old /post/feed/, /category/x/, /blog/page/2/ …) still pass through the web
// middleware so HandleRedirects can 301 them; anything without a redirect is a normal 404.
Route::fallback(fn () => abort(404));
