<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Blog;
use App\Models\CaseStudy;
use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\LandingPage;
use App\Models\Media;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\MediaService;
use App\Services\SettingsService;
use App\Support\ContentCache;
use App\Support\FrontendRevalidator;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Idempotently imports original visual assets migrated from the old Tech Alphonic website
 * (public/assets/migrated-old-site/) into the Laravel Media Library and assigns them to their
 * corresponding CMS records without modifying any layout, copy, or user-uploaded brand/hero media.
 */
class OldSiteImageMigrationSeeder extends Seeder
{
    /**
     * Canonical local asset paths (relative to public/) => [alt, title, folder].
     */
    private const ASSETS = [
        'hero_poster' => [
            'assets/migrated-old-site/homepage/Futuristic-AI-Marketing-Growth-Banner.png',
            'Tech Alphonic AI-powered digital marketing and growth banner',
            'Hero Background Banner',
            'home',
        ],
        'services_bento' => [
            'assets/migrated-old-site/homepage/our_Result_oriented_featuirs1.webp',
            'Strategy meeting aligning digital marketing with brand goals',
            'Result-Oriented Digital Strategy',
            'home',
        ],
        'about_ai' => [
            'assets/migrated-old-site/homepage/AI-Powered_Digital_Marketing_Company1.webp',
            'AI-powered digital marketing analytics and channel strategy',
            'AI-Powered Digital Marketing',
            'home',
        ],
        'showreel_team' => [
            'assets/migrated-old-site/homepage/Collaborative-Analytics-Team-Workspace.png',
            'Tech Alphonic digital marketing and analytics team collaborating',
            'Collaborative Analytics Team Workspace',
            'home',
        ],
        'about_collaboration' => [
            'assets/migrated-old-site/homepage/Ready_To_Grow_your_business1.webp',
            'Tech Alphonic specialists reviewing business growth strategy',
            'About Tech Alphonic Specialists',
            'about',
        ],
        'prove_results' => [
            'assets/migrated-old-site/case-studies/Prove_Results1.webp',
            'Global search visibility and measurable digital growth results',
            'Proven Digital Growth Results',
            'case-studies',
        ],
        'brand_logo_full' => [
            'assets/migrated-old-site/logos/tech-new-logo.png',
            'Tech Alphonic — Your Trusted IT Partner',
            'Tech Alphonic Official Logo',
            'brand',
        ],
        'brand_icon_square' => [
            'assets/migrated-old-site/logos/cropped-tech-new-logo.png',
            'Tech Alphonic brand emblem',
            'Tech Alphonic Brand Icon',
            'brand',
        ],
        'digital_marketing_cat' => [
            'assets/migrated-old-site/services/25225467_7016019-2048x1575-1.webp',
            'Digital marketing and collaborative growth workflow illustration',
            'Digital Marketing Solutions',
            'services',
        ],
        'development_cat' => [
            'assets/migrated-old-site/services/e7557188-2a1c-40c3-beb1-31ef1e8c4c85.png',
            'Tech Alphonic intelligent web, app, cloud and AI development solutions',
            'Intelligent Digital Solutions',
            'services',
        ],
        'ai_seo_tablet' => [
            'assets/migrated-old-site/services/Gemini_Generated_Image_wt21orwt21orwt21.webp',
            'AI SEO visibility score and topical authority dashboard',
            'AI SEO Services Dashboard',
            'services',
        ],
        'ai_overview_seo' => [
            'assets/migrated-old-site/services/Gemini_Generated_Image_w6va0mw6va0mw6va.webp',
            'Contextual analysis, entity mapping and AI Overview SEO architecture',
            'AI Overview & Search Architecture',
            'services',
        ],
        'llm_optimization' => [
            'assets/migrated-old-site/services/Untitled-3-1.webp',
            'LLM optimization and search visibility diagram',
            'Technical & LLM Search Optimization',
            'services',
        ],
        'on_page_ai_seo' => [
            'assets/migrated-old-site/services/Untitled-6.webp',
            'AI search engine optimization and content signals',
            'Search Optimization Signals',
            'services',
        ],
        'geo_seo' => [
            'assets/migrated-old-site/services/Untitled-5-1-1.webp',
            'Generative engine optimization visual',
            'GEO & Off-Page Search Authority',
            'services',
        ],
        'aeo_seo' => [
            'assets/migrated-old-site/services/Untitled-5.webp',
            'Answer engine optimization and search discovery',
            'Search Discovery & Optimization',
            'services',
        ],
        'content_blog_tiles' => [
            'assets/migrated-old-site/services/pexels-pixabay-262508-scaled.jpg',
            'Editorial content marketing and SEO copywriting',
            'Content Marketing',
            'services',
        ],
        'marketing_specialist' => [
            'assets/migrated-old-site/services/shot-of-a-young-businesswoman-using-a-digital-tabl-2022-10-06-03-37-11-utc.webp',
            'Digital marketing specialist reviewing campaign analytics',
            'Digital Marketing Specialist',
            'services',
        ],
        'india_banner' => [
            'assets/migrated-old-site/services/untitled-design-2-33cgdne3.png',
            'Digital Marketing Company in India — SEO, Social Media, PPC & Web Solutions',
            'Digital Marketing Company in India',
            'locations',
        ],
        'noida_banner' => [
            'assets/migrated-old-site/services/digital-marketing-growth-in-noida-wdripeqo.png',
            'Digital Marketing Company in Noida — SEO, PPC, Social Media & Web Design',
            'Digital Marketing Company in Noida',
            'locations',
        ],
        'ghaziabad_banner' => [
            'assets/migrated-old-site/services/digital-marketing-company-in-ghaziabad-hamoktcr.png',
            'Digital Marketing Company in Ghaziabad — Grow Your Business Online',
            'Digital Marketing Company in Ghaziabad',
            'locations',
        ],
    ];

    /**
     * Restore missing physical disk files for pre-existing Media rows (IDs 1..14) without deleting rows.
     */
    private const RESTORE_MISSING_MEDIA = [
        'live-marketing-dashboard.webp' => 'public:assets/migrated-old-site/homepage/digitalize_your_marketing_with_us2.webp',
        'ayurgrid.jpg' => 'public:assets/migrated-old-site/logos/ayurgrid.jpg',
        'lawgical.png' => 'public:assets/migrated-old-site/logos/lawgical.png',
        'kitchen-fix-solution-logo.png' => 'public:assets/migrated-old-site/logos/kitchen-fix-solution-logo.png',
        'desi-guru.png' => 'public:assets/migrated-old-site/logos/desi-guru.png',
        'logo-main.png' => 'public:assets/migrated-old-site/logos/logo-main.png',
        'Services_Plus.webp' => 'public:assets/migrated-old-site/logos/Services_Plus.webp',
        'Com-Paint_Round_Logo.webp' => 'public:assets/migrated-old-site/logos/Com-Paint_Round_Logo-1.webp',
        'Enlive.webp' => 'public:assets/migrated-old-site/logos/Enlive.webp',
        'hero-team.mp4' => 'db:seeders/media/hero-team.mp4',
        'hero-poster.jpg' => 'db:seeders/media/hero-poster.jpg',
        'about-team.jpg' => 'db:seeders/media/about-team.jpg',
        'showreel.jpg' => 'db:seeders/media/showreel.jpg',
        'dev-pair.jpg' => 'db:seeders/media/dev-pair.jpg',
    ];

    /**
     * Verified official website URLs for the 8 Valued Clients logos.
     */
    private const CLIENT_URLS = [
        'AyurGrid' => 'https://ayurgrid.com/',
        'Lawgical' => 'https://www.lawgicalindia.com/',
        'Kitchen Fix Solution' => 'https://kitchenfixsolution.com/',
        'Desi Guru' => 'https://desigurudrivingschool.com/',
        'Chardham Yatra' => 'https://chardhamyatra.org/',
        'Services Plus' => 'https://www.servicesplus.in/',
        'Com-Paint' => 'https://www.com-paint.com/',
        'Enlive' => 'https://www.enlivetrips.com/',
    ];

    public function run(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $mediaService = app(MediaService::class);
        $this->restoreMissingMediaFiles($mediaService);
        $ids = [];

        foreach (self::ASSETS as $key => [$relPath, $alt, $title, $folder]) {
            $absPath = public_path($relPath);
            if (! is_file($absPath)) {
                $this->command?->warn("Missing archival asset: {$relPath}");
                continue;
            }

            $fileName = basename($absPath);
            $existing = Media::where('original_name', $fileName)->first();
            if ($existing) {
                $ids[$key] = $existing->id;
                continue;
            }

            $mime = mime_content_type($absPath) ?: 'application/octet-stream';
            $upload = new UploadedFile($absPath, $fileName, $mime, null, true);
            $ids[$key] = $mediaService->store($upload, [
                'alt' => $alt,
                'title' => $title,
                'folder' => $folder,
            ])->id;
        }

        // 1. Settings (preserve user-uploaded logo=15, logo_dark=16, favicon=17)
        $settings = app(SettingsService::class);
        if (isset($ids['brand_logo_full'])) {
            $settings->set('default_og_image', $ids['brand_logo_full'], 'seo');
        }
        if (isset($ids['about_collaboration'])) {
            $currentPopupId = (int) $settings->get('popup_image');
            $currentPopupName = $currentPopupId ? Media::whereKey($currentPopupId)->value('original_name') : null;
            if (! $currentPopupId || $currentPopupName === 'about-team.jpg') {
                $settings->set('popup_image', $ids['about_collaboration'], 'popup');
            }
        }

        // 2. Hero Slide (preserve user-uploaded image_id=19 and background_video_id=20; fill NULL background_id)
        if (($hero = HeroSlide::orderBy('sort_order')->first()) && isset($ids['hero_poster'])) {
            if (empty($hero->background_id)) {
                $hero->update(['background_id' => $ids['hero_poster']]);
            }
        }

        // 3. Home Sections (fix broken/stock mappings while preserving promo=5 & clients=7..14)
        $this->updateHomeSection('services', ['image_id' => $ids['services_bento'] ?? null]);
        $this->updateHomeSection('about', ['image_id' => $ids['about_ai'] ?? null]);

        // Fix showreel: replace duplicate promo image_id=5 and invalid .jpg video_id=2
        $mp4VideoId = Media::where('mime_type', 'video/mp4')->orderByDesc('id')->value('id');
        $this->updateHomeSection('showreel', array_filter([
            'image_id' => $ids['showreel_team'] ?? null,
            'video_id' => $mp4VideoId,
        ]));

        // Populate verified website URLs for Valued Clients without overwriting custom admin-set links
        $clientsSection = HomeSection::where('key', 'clients')->first();
        if ($clientsSection && is_array($clientsSection->content['items'] ?? null)) {
            $updated = false;
            $items = array_map(function ($item) use (&$updated) {
                $name = $item['name'] ?? null;
                if ($name && empty($item['url']) && isset(self::CLIENT_URLS[$name])) {
                    $item['url'] = self::CLIENT_URLS[$name];
                    $updated = true;
                }

                return $item;
            }, $clientsSection->content['items']);

            if ($updated) {
                $clientsSection->update([
                    'content' => array_merge($clientsSection->content, ['items' => $items]),
                ]);
            }
        }

        // 4. CMS Pages
        if (isset($ids['about_collaboration'])) {
            Page::where('slug', 'about-us')->update(['featured_image_id' => $ids['about_collaboration']]);
        }
        if (isset($ids['prove_results'])) {
            Page::where('slug', 'seo-by-industry')->update(['featured_image_id' => $ids['prove_results']]);
        }

        // 5. Case Studies (replace stretched 195x73 ayurgrid.jpg logo)
        if (isset($ids['prove_results'])) {
            CaseStudy::where('slug', 'ayurveda-clinic-software-seo')->update([
                'featured_image_id' => $ids['prove_results'],
            ]);
        }

        // 6. Service Categories
        if (isset($ids['digital_marketing_cat'])) {
            ServiceCategory::where('slug', 'digital-marketing')->update(['image_id' => $ids['digital_marketing_cat']]);
        }
        if (isset($ids['development_cat'])) {
            ServiceCategory::where('slug', 'development')->update(['image_id' => $ids['development_cat']]);
        }

        // 7. Services (all 20 services)
        $serviceMap = [
            'search-engine-optimization' => 'ai_overview_seo',
            'local-seo' => 'digital_marketing_cat',
            'ecommerce-seo' => 'marketing_specialist',
            'technical-seo' => 'llm_optimization',
            'ai-seo-services' => 'ai_seo_tablet',
            'on-page-seo' => 'on_page_ai_seo',
            'off-page-seo-services' => 'geo_seo',
            'app-store-seo' => 'aeo_seo',
            'content-marketing' => 'content_blog_tiles',
            'social-media' => 'marketing_specialist',
            'pay-per-click' => 'marketing_specialist',
            'branding' => 'marketing_specialist',
            'website-development' => 'development_cat',
            'custom-web-development' => 'development_cat',
            'laravel-development' => 'development_cat',
            'wordpress-development' => 'development_cat',
            'react-js-development' => 'development_cat',
            'shopify-development' => 'development_cat',
            'app-development' => 'development_cat',
            'ui-ux' => 'development_cat',
        ];
        foreach ($serviceMap as $slug => $assetKey) {
            if (isset($ids[$assetKey])) {
                Service::where('slug', $slug)->update([
                    'hero_image_id' => $ids[$assetKey],
                    'featured_image_id' => $ids[$assetKey],
                ]);
            }
        }

        // 8. Landing Pages
        $specificLandings = [
            'seo-company-in-india' => 'india_banner',
            'seo-company-in-noida' => 'noida_banner',
            'search-engine-optimization-company-in-ghaziabad' => 'ghaziabad_banner',
            'search-engine-optimization-for-real-estate-in-noida' => 'noida_banner',
            'seo-for-moving-company' => 'digital_marketing_cat',
            'seo-for-pest-control-companies' => 'digital_marketing_cat',
            'plumbing-seo-services' => 'digital_marketing_cat',
            'hospitality-seo-services' => 'digital_marketing_cat',
            'seo-for-transport-and-logistics' => 'digital_marketing_cat',
        ];
        foreach ($specificLandings as $slug => $assetKey) {
            if (isset($ids[$assetKey])) {
                LandingPage::where('slug', $slug)->update(['featured_image_id' => $ids[$assetKey]]);
            }
        }
        if (isset($ids['ai_overview_seo'])) {
            LandingPage::whereNotIn('slug', array_keys($specificLandings))
                ->whereNull('featured_image_id')
                ->update(['featured_image_id' => $ids['ai_overview_seo']]);
        }

        // 9. Blogs & Author Avatar
        $blogMap = [
            'local-seo-checklist-for-small-businesses' => 'digital_marketing_cat',
            'how-to-choose-a-web-development-company' => 'development_cat',
            'programmatic-seo-without-thin-content' => 'ai_overview_seo',
        ];
        foreach ($blogMap as $slug => $assetKey) {
            if (isset($ids[$assetKey])) {
                Blog::where('slug', $slug)->update(['featured_image_id' => $ids[$assetKey]]);
            }
        }
        if (isset($ids['brand_icon_square'])) {
            Author::where('slug', 'tech-alphonic-team')->whereNull('avatar_id')->update([
                'avatar_id' => $ids['brand_icon_square'],
            ]);
        }

        ContentCache::flush();
        $settings->flush();
        FrontendRevalidator::revalidateNow();
    }

    private function restoreMissingMediaFiles(MediaService $mediaService): void
    {
        foreach (Media::orderBy('id')->get() as $media) {
            $disk = Storage::disk($media->disk);
            $mainExists = $disk->exists($media->path);
            $variantsOk = true;
            foreach (($media->variants ?? []) as $variantPath) {
                if (! $disk->exists($variantPath)) {
                    $variantsOk = false;
                    break;
                }
            }
            if ($mainExists && $variantsOk) {
                continue;
            }

            $spec = self::RESTORE_MISSING_MEDIA[$media->original_name] ?? null;
            if (! $spec) {
                $this->command?->warn("Missing disk file for Media ID {$media->id} ({$media->original_name}) and no restore source mapped.");
                continue;
            }

            $absPath = str_starts_with($spec, 'public:')
                ? public_path(substr($spec, 7))
                : database_path(substr($spec, 3));

            if (! is_file($absPath)) {
                $this->command?->warn("Restore source not found for Media ID {$media->id}: {$absPath}");
                continue;
            }

            $mime = mime_content_type($absPath) ?: $media->mime_type;
            $upload = new UploadedFile($absPath, $media->original_name, $mime, null, true);
            $mediaService->replace($media, $upload);
        }
    }

    private function updateHomeSection(string $key, array $patch): void
    {
        $patch = array_filter($patch, fn ($v) => $v !== null);
        if (! $patch) {
            return;
        }
        $section = HomeSection::where('key', $key)->first();
        if ($section) {
            $section->update(['content' => array_merge($section->content ?? [], $patch)]);
        }
    }
}
