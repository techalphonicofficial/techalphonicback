<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        // [slug, name, plural, icon, challenges, solutions]
        $industries = [
            ['real-estate', 'Real Estate', 'Real Estate Companies', 'building',
                ['Portals dominate project and locality searches', 'High cost per lead on paid ads', 'Long, research-heavy buyer journeys'],
                ['Locality and project landing pages with schema', 'Lead-quality tracking from enquiry to site visit', 'Content that answers buyer questions at each stage']],
            ['healthcare', 'Healthcare', 'Healthcare Providers', 'stethoscope',
                ['Strict trust signals required (E-E-A-T)', 'Patients search “near me” on mobile', 'Reviews strongly influence choice'],
                ['Doctor and treatment pages with medical-review workflow', 'Google Business Profile optimisation', 'Review generation and reputation management']],
            ['travel', 'Travel', 'Travel Companies', 'plane',
                ['OTAs outrank direct operators', 'Seasonal demand swings', 'Thin, duplicated itinerary content'],
                ['Destination and itinerary content hubs', 'Seasonal campaign planning', 'Unique, experience-led package pages']],
            ['ecommerce', 'Ecommerce', 'Ecommerce Brands', 'cart',
                ['Large catalogues with crawl and duplicate issues', 'Marketplaces compete for the same queries', 'Rising paid acquisition costs'],
                ['Technical SEO for faceted navigation', 'Category and product content optimisation', 'Shopping feeds and ROAS-focused ads']],
            ['hospitality', 'Hospitality', 'Hotels & Restaurants', 'hotel',
                ['Booking platforms take a commission', 'Visibility in maps and local packs', 'Inconsistent listings across platforms'],
                ['Direct-booking focused website and SEO', 'Local SEO and review strategy', 'Rich results with hotel/restaurant schema']],
            ['logistics', 'Transport & Logistics', 'Transport & Logistics Companies', 'truck',
                ['B2B buyers with long decision cycles', 'Service-area targeting across many cities', 'Low brand awareness online'],
                ['Service + city landing pages', 'B2B lead generation and LinkedIn', 'Case studies that prove reliability']],
            ['moving-company', 'Moving Companies', 'Packers & Movers', 'truck',
                ['Aggressive competition and fake listings', 'Urgent, high-intent mobile searches', 'Price-sensitive customers'],
                ['Route and city pages (e.g. Noida to Bangalore)', 'Click-to-call and WhatsApp optimisation', 'Review and trust-building strategy']],
            ['pest-control', 'Pest Control', 'Pest Control Companies', 'bug',
                ['Hyper-local, emergency searches', 'Seasonal spikes', 'Competition from aggregator apps'],
                ['Local SEO across every service area', 'Service-specific pages (termite, cockroach, bed bugs)', 'Call tracking and fast-response funnels']],
            ['plumbing', 'Plumbing', 'Plumbing Businesses', 'wrench',
                ['“Near me” and emergency intent', 'Small budgets per job', 'Trust and response time matter most'],
                ['Google Business Profile and local citations', 'Emergency-service landing pages', 'Reviews and before/after content']],
            ['education', 'Education', 'Schools & Institutes', 'graduation',
                ['Admission seasons drive demand', 'Parents compare many options', 'Course pages lack detail'],
                ['Course and campus pages with FAQs', 'Admission-season campaigns', 'Lead nurturing for enquiries']],
            ['finance', 'Finance', 'Financial Services Firms', 'banknote',
                ['YMYL content scrutiny', 'Regulated messaging', 'High cost per click'],
                ['Expert-reviewed educational content', 'Compliance-aware landing pages', 'Calculator and tool-led SEO']],
            ['manufacturing', 'Manufacturing', 'Manufacturers', 'factory',
                ['Complex products, niche keywords', 'Export and B2B buyers', 'Outdated websites'],
                ['Product and application pages with specs', 'International SEO readiness', 'RFQ-focused lead generation']],
            ['saas', 'SaaS', 'SaaS Companies', 'cloud',
                ['Competitive, global SERPs', 'Long trial-to-paid journeys', 'Comparison and alternative searches'],
                ['Bottom-funnel comparison content', 'Programmatic integration/use-case pages', 'Product-led content strategy']],
        ];

        foreach ($industries as $i => [$slug, $name, $plural, $icon, $challenges, $solutions]) {
            Industry::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'plural_name' => $plural, 'icon' => $icon, 'sort_order' => $i,
                'description' => "Digital marketing and technology solutions built for {$plural} — from local visibility to lead generation.",
                'challenges' => array_map(fn ($c) => ['title' => $c], $challenges),
                'solutions' => array_map(fn ($s) => ['title' => $s], $solutions),
                'status' => 'published',
            ]);
        }
    }
}
