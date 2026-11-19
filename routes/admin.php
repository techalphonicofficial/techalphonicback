<?php

use App\Http\Controllers\Admin;
use App\Models\Redirect;
use App\Services\SettingsService;
use App\Support\ContentCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/*
| Admin panel. Prefix comes from ADMIN_PATH (.env) so the URL needn't be the obvious /admin.
| Included from routes/web.php before the public /{slug} catch-all.
*/

Route::prefix(config('site.admin_path'))->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'login'])->middleware('throttle:admin-login')->name('login.attempt');
    });

    Route::middleware(['auth', 'active'])->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        // CMS content types share the ContentController workflow.
        $content = [
            'pages' => Admin\PageController::class,
            'service-categories' => Admin\ServiceCategoryController::class,
            'services' => Admin\ServiceController::class,
            'landing-pages' => Admin\LandingPageController::class,
            'locations' => Admin\LocationController::class,
            'industries' => Admin\IndustryController::class,
            'blogs' => Admin\BlogController::class,
            'blog-categories' => Admin\BlogCategoryController::class,
            'authors' => Admin\AuthorController::class,
            'faqs' => Admin\FaqController::class,
            'testimonials' => Admin\TestimonialController::class,
            'case-studies' => Admin\CaseStudyController::class,
            'portfolio' => Admin\PortfolioController::class,
            'redirects' => Admin\RedirectController::class,
            'schemas' => Admin\SchemaTemplateController::class,
        ];

        // Programmatic SEO extras (registered before the generic {id} routes).
        Route::controller(Admin\LandingPageController::class)->prefix('landing-pages')->name('landing-pages.')->group(function () {
            Route::get('bulk', 'bulkForm')->name('bulk');
            Route::post('bulk', 'bulkGenerate')->name('bulk.generate');
            Route::get('bulk-seo', 'bulkSeo')->name('bulk-seo');
            Route::put('bulk-seo', 'bulkSeoUpdate')->name('bulk-seo.update');
            Route::post('bulk-action', 'bulkAction')->name('bulk-action');
            Route::post('{id}/ai', 'aiGenerate')->whereNumber('id')->name('ai');
            Route::get('{id}/ai-status', 'aiStatus')->whereNumber('id')->name('ai-status');
        });
        Route::post('faqs/reorder', [Admin\FaqController::class, 'reorder'])->name('faqs.reorder');
        Route::post('faqs/quick-store', [Admin\FaqController::class, 'quickStore'])->name('faqs.quick-store');
        Route::post('redirects/import', [Admin\RedirectController::class, 'import'])->name('redirects.import');
        Route::get('redirects/export', [Admin\RedirectController::class, 'export'])->name('redirects.export');

        foreach ($content as $uri => $controller) {
            Route::controller($controller)->prefix($uri)->name($uri . '.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{id}/edit', 'edit')->whereNumber('id')->name('edit');
                Route::put('{id}', 'update')->whereNumber('id')->name('update');
                Route::delete('{id}', 'destroy')->whereNumber('id')->name('destroy');
                Route::post('{id}/restore', 'restore')->whereNumber('id')->name('restore');
                Route::post('{id}/publish', 'publish')->whereNumber('id')->name('publish');
                Route::post('{id}/unpublish', 'unpublish')->whereNumber('id')->name('unpublish');
                Route::get('{id}/preview', 'preview')->whereNumber('id')->name('preview');
                Route::post('{id}/duplicate', 'duplicate')->whereNumber('id')->name('duplicate');
            });
        }

        Route::get('home', [Admin\HomeController::class, 'edit'])->name('home.edit');
        Route::put('home/hero', [Admin\HomeController::class, 'updateHero'])->name('home.hero');
        Route::put('home/sections', [Admin\HomeController::class, 'updateSections'])->name('home.sections');

        Route::controller(Admin\MediaController::class)->prefix('media')->name('media.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->middleware('throttle:60,1')->name('store');
            Route::put('{id}', 'update')->whereNumber('id')->name('update');
            Route::post('{id}/replace', 'replace')->whereNumber('id')->name('replace');
            Route::post('{id}/crop', 'crop')->whereNumber('id')->name('crop');
            Route::delete('{id}', 'destroy')->whereNumber('id')->name('destroy');
        });

        Route::controller(Admin\LeadController::class)->prefix('leads')->name('leads.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('export', 'export')->name('export');
            Route::get('{id}', 'show')->whereNumber('id')->name('show');
            Route::put('{id}', 'update')->whereNumber('id')->name('update');
            Route::delete('{id}', 'destroy')->whereNumber('id')->name('destroy');
        });

        Route::get('menus', [Admin\MenuController::class, 'index'])->name('menus.index');
        Route::get('menus/{location}', [Admin\MenuController::class, 'edit'])->name('menus.edit');
        Route::put('menus/{location}', [Admin\MenuControllker::class, 'update'])->name('menus.update');

        Route::get('sitemap-import', [Admin\SitemapImportController::class, 'index'])->name('sitemap-import.index');
        Route::post('sitemap-import', [Admin\SitemapImportController::class, 'import'])->name('sitemap-import.import');
        Route::put('sitemap-import/{id}', [Admin\SitemapImportController::class, 'update'])->whereNumber('id')->name('sitemap-import.update');
        Route::post('sitemap-import/redirects', [Admin\SitemapImportController::class, 'createRedirects'])->name('sitemap-import.redirects');

        Route::get('settings/{group?}', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings/social/links', [Admin\SettingsController::class, 'updateSocial'])->name('settings.social');
        Route::put('settings/{group}', [Admin\SettingsController::class, 'update'])->name('settings.update');

        Route::controller(Admin\AiController::class)->prefix('ai')->name('ai.')->group(function () {
            Route::get('settings', 'edit')->name('settings');
            Route::put('settings', 'update')->name('settings.update');
            Route::post('settings/test', 'test')->middleware('throttle:20,1')->name('settings.test');
            Route::post('settings/models', 'models')->middleware('throttle:20,1')->name('settings.models');
            Route::get('generations', 'generations')->name('generations');
        });

        Route::resource('users', Admin\UserController::class)->except('show');
        Route::resource('roles', Admin\RoleController::class)->except('show');
        Route::get('activity', Admin\ActivityLogController::class)->name('activity.index');

        // Deploy commands for hosts without SSH (whitelisted in MaintenanceController, system.maintenance only).
        Route::controller(Admin\MaintenanceController::class)->prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('frontend/{action}', 'frontend')->middleware('throttle:20,1')->name('frontend');
            Route::post('{action}', 'run')->middleware('throttle:20,1')->name('run');
        });

        Route::post('cache/clear', function () {
            abort_unless(request()->user()->can('settings.manage'), 403);
            ContentCache::flush();
            Cache::forget(Redirect::CACHE_KEY);
            app(SettingsService::class)->flush();

            return back()->with('success', 'Website cache cleared.');
        })->name('cache.clear');

    });
});
