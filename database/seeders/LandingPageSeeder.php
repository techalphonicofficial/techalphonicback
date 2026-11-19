<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\LandingPageType;
use App\Models\Faq;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Location;
use App\Models\Redirect;
use App\Models\Service;
use App\Services\LandingPageGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Recreates the programmatic SEO URLs that exist on the current site (published, indexable, unique copy)
 * and adds a few generator drafts to demonstrate the review workflow.
 */
class LandingPageSeeder extends Seeder
{
    public function run(): void
    {
        $gen = app(LandingPageGenerator::class);
        $seo = Service::where('slug', 'search-engine-optimization')->firstOrFail();
        $locationFaq = Faq::where('question', 'like', 'Will SEO work for my business in%')->value('id');

        $legacy = [
            // [type, location slug, industry slug, legacy slug, h1, intro, extra section]
            [LandingPageType::ServiceLocation, 'india', null, 'seo-company-in-india', 'SEO Company in India',
                '<p>Tech Alphonic is an SEO company in India helping brands across the country win more organic traffic and enquiries. From national ecommerce stores to multi-city service businesses, we build search strategies around real customer demand — and report on leads, not just rankings.</p>',
                ['heading' => 'National reach, local relevance', 'content' => '<p>Ranking across India means balancing national keywords with city-level intent. We combine strong core service pages with carefully researched location pages, so you appear for both “SEO services” and “SEO company in Pune” without creating thin duplicate content.</p>']],
            [LandingPageType::ServiceLocation, 'noida', null, 'seo-company-in-noida', 'SEO Company in Noida',
                '<p>Looking for an SEO company in Noida you can actually meet? Our team works from Sector 63, helping Noida and NCR businesses — from IT firms in Sector 62 to real-estate developers along the Expressway — rank higher and generate qualified leads.</p>',
                ['heading' => 'Why Noida businesses choose a local SEO partner', 'content' => '<p>Noida’s market is crowded with agencies and aggregators. Working with a local team means strategy sessions in person, faster communication and a sharper understanding of how customers in Noida, Greater Noida and Ghaziabad search.</p>']],
            [LandingPageType::ServiceIndustry, null, 'moving-company', 'seo-for-moving-company', 'SEO for Moving Companies',
                '<p>Packers and movers compete for urgent, high-intent searches — often against aggregators and fake listings. Our SEO for moving companies focuses on route pages, Google Business Profile visibility and trust signals that turn searchers into booked moves.</p>',
                ['heading' => 'Route pages that convert', 'content' => '<p>Customers search for specific moves, like “packers and movers Noida to Bangalore”. We build helpful route pages with pricing guidance, checklists and FAQs so you rank for these valuable searches.</p>']],
            [LandingPageType::ServiceIndustry, null, 'pest-control', 'seo-for-pest-control-companies', 'SEO for Pest Control Companies',
                '<p>When someone finds termites or cockroaches, they search and call within minutes. Our SEO for pest control companies puts you in the map pack and on page one for service-specific, emergency and “near me” searches across every area you serve.</p>',
                ['heading' => 'Service-specific visibility', 'content' => '<p>Separate, detailed pages for termite control, bed-bug treatment, mosquito control and commercial pest management help you rank for exactly what customers need — and answer their questions before they call.</p>']],
            [LandingPageType::ServiceIndustry, null, 'plumbing', 'plumbing-seo-services', 'Plumbing SEO Services',
                '<p>Plumbing customers rarely compare ten websites — they call the first trustworthy business they find. Our plumbing SEO services focus on local rankings, reviews and click-to-call so your phone rings for the jobs you want.</p>',
                ['heading' => 'Built for emergency searches', 'content' => '<p>We optimise for urgent queries like “leak repair near me”, make your phone number prominent on mobile and track every call so you can see exactly which searches generate jobs.</p>']],
            [LandingPageType::ServiceIndustry, null, 'hospitality', 'hospitality-seo-services', 'Hospitality SEO Services',
                '<p>Hotels and restaurants lose margin to booking platforms. Our hospitality SEO services grow direct bookings by improving visibility in maps, local packs and destination searches — with rich results that show ratings, prices and availability.</p>',
                ['heading' => 'More direct bookings', 'content' => '<p>We pair technical SEO and structured data with destination content and review strategy so guests find you — and book directly.</p>']],
            [LandingPageType::ServiceIndustry, null, 'logistics', 'seo-for-transport-and-logistics', 'SEO for Transport & Logistics Companies',
                '<p>Logistics buyers research carefully before shortlisting partners. Our SEO for transport and logistics companies builds authority around your services, lanes and capabilities so procurement teams find you at the start of their search.</p>',
                ['heading' => 'B2B search strategy', 'content' => '<p>Service, lane and industry pages — supported by case studies and clear capability information — help you rank for high-value B2B queries and generate RFQs.</p>']],
        ];

        foreach ($legacy as [$type, $locSlug, $indSlug, $slug, $h1, $intro, $section]) {
            $location = $locSlug ? Location::where('slug', $locSlug)->firstOrFail() : null;
            $industry = $indSlug ? Industry::where('slug', $indSlug)->firstOrFail() : null;

            $page = LandingPage::where('slug', $slug)->first()
                ?? $gen->create($type, $seo, $location, $industry, 'manual')
                ?? LandingPage::where('service_id', $seo->id)->where('location_id', $location?->id)->where('industry_id', $industry?->id)->first();

            $page->fill([
                'slug' => $slug,
                'title' => $h1,
                'h1' => $h1,
                'introduction' => $intro,
                'sections' => [$section],
                'hero_layout' => $industry ? 'centered' : 'split',
                'status' => ContentStatus::Published,
                'published_at' => now(),
            ])->save();
            $page->syncSeo([
                'meta_title' => $h1.' | Tech Alphonic',
                'meta_description' => strip_tags(Str::limit(strip_tags($intro), 155, '…')),
                'focus_keyword' => strtolower($h1),
                'robots_index' => true,
                'robots_follow' => true,
                'include_in_sitemap' => true,
            ]);
            if ($location && $locationFaq) {
                $page->syncFaqs(array_merge($seo->faqs()->pluck('faqs.id')->all(), [$locationFaq]));
            }
        }

        // Renaming freshly generated slugs to the legacy URLs isn't a real URL change — drop those auto-redirects.
        Redirect::where('source', 'slug_change')->delete();

        // Demo of the review workflow: generated drafts (noindex) awaiting unique content.
        foreach (['delhi', 'ghaziabad', 'gurgaon', 'greater-noida'] as $slug) {
            $gen->create(LandingPageType::ServiceLocation, $seo, Location::where('slug', $slug)->first(), null, 'bulk');
        }
        $realEstate = Industry::where('slug', 'real-estate')->first();
        $gen->create(LandingPageType::ServiceLocationIndustry, $seo, Location::where('slug', 'noida')->first(), $realEstate, 'generator');
    }
}
