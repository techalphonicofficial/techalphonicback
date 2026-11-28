<?php

namespace App\Providers;

use App\Ai\AiManager;
use App\Models;
use App\Policies\ContentPolicy;
use App\Services\FaqService;
use App\Services\MenuService;
use App\Services\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(MenuService::class);
        $this->app->singleton(FaqService::class);
        $this->app->singleton(AiManager::class);
    }

    public function boot(): void
    {
        // Every generated URL (sitemaps, canonicals, schema, menus) uses APP_URL — the public domain — rather than
        // whichever host the request came in on (the Next.js proxy and the API reach Laravel on its own host).
        // In local HTTP requests, keep the incoming host (127.0.0.1, localhost, or LAN IP) so session cookies and
        // form actions stay on the same origin.
        if (($root = rtrim((string) config('app.url'), '/')) && (! $this->app->isLocal() || $this->app->runningInConsole())) {
            URL::forceRootUrl($root);
            URL::forceScheme(parse_url($root, PHP_URL_SCHEME) ?: 'https');
        }

        // Short, stable morph aliases (stored in url_slugs, seo_meta, page_blocks, page_faqs…).
        Relation::enforceMorphMap([
            'user' => Models\User::class,
            'page' => Models\Page::class,
            'service' => Models\Service::class,
            'service_category' => Models\ServiceCategory::class,
            'landing_page' => Models\LandingPage::class,
            'location' => Models\Location::class,
            'industry' => Models\Industry::class,
            'blog' => Models\Blog::class,
            'portfolio' => Models\Portfolio::class,
            'case_study' => Models\CaseStudy::class,
            'faq' => Models\Faq::class,
            'media' => Models\Media::class,
            'lead' => Models\Lead::class,
            'menu_item' => Models\MenuItem::class,
            'testimonial' => Models\Testimonial::class,
            'home_section' => Models\HomeSection::class,
            'hero_slide' => Models\HeroSlide::class,
            'redirect' => Models\Redirect::class,
            'setting' => Models\Setting::class,
            'blog_category' => Models\BlogCategory::class,
            'blog_tag' => Models\BlogTag::class,
            'faq_category' => Models\FaqCategory::class,
            'author' => Models\Author::class,
            'schema_template' => Models\SchemaTemplate::class,
            'menu' => Models\Menu::class,
            'lead_note' => Models\LeadNote::class,
            'seo_meta' => Models\SeoMeta::class,
            'page_block' => Models\PageBlock::class,
            'internal_link' => Models\InternalLink::class,
            'url_slug' => Models\UrlSlug::class,
            'social_link' => Models\SocialLink::class,
            'sitemap_import' => Models\SitemapImport::class,
            'activity_log' => Models\ActivityLog::class,
            'ai_generation' => Models\AiGeneration::class,
        ]);

        // Surface N+1 queries in the dev log (without breaking pages) and mass-assignment mistakes.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::handleLazyLoadingViolationUsing(fn ($model, $relation) => Log::debug(
            'Lazy loaded '.get_class($model).'::'.$relation.' — consider eager loading.'
        ));
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Password::defaults(fn () => Password::min(10)->mixedCase()->numbers()->symbols()
            ->when($this->app->isProduction(), fn ($rule) => $rule->uncompromised()));

        Gate::before(fn ($user) => $user->hasRole('Super Admin') ? true : null);
        foreach ([Models\Page::class, Models\Service::class, Models\ServiceCategory::class, Models\LandingPage::class,
            Models\Location::class, Models\Industry::class, Models\Blog::class, Models\Portfolio::class, Models\CaseStudy::class,
            Models\Testimonial::class, Models\Faq::class, Models\Media::class, Models\Redirect::class, Models\HeroSlide::class,
            Models\HomeSection::class] as $model) {
            Gate::policy($model, ContentPolicy::class);
        }

        RateLimiter::for('leads', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);
        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        // The Next.js server renders every page through the site API, so it is trusted via a shared key.
        RateLimiter::for('site', function (Request $request) {
            $key = (string) config('services.frontend.key');

            return $key !== '' && hash_equals($key, (string) $request->header('X-Frontend-Key'))
                ? Limit::none()
                : Limit::perMinute(120)->by($request->ip());
        });

        Paginator::defaultView('admin.partials.pagination');
    }
}
