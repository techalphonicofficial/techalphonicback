<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $general = FaqCategory::updateOrCreate(['slug' => 'general'], ['name' => 'General', 'sort_order' => 0]);
        $seoCat = FaqCategory::updateOrCreate(['slug' => 'seo'], ['name' => 'SEO', 'sort_order' => 1]);
        $devCat = FaqCategory::updateOrCreate(['slug' => 'development'], ['name' => 'Development', 'sort_order' => 2]);

        $make = fn ($cat, $q, $a, $order) => Faq::updateOrCreate(['question' => $q], ['faq_category_id' => $cat->id, 'answer' => $a, 'is_active' => true, 'sort_order' => $order]);

        $generalFaqs = [
            $make($general, 'What services does Tech Alphonic offer?', '<p>We offer SEO, local SEO, ecommerce SEO, PPC, social media marketing, content marketing and branding, plus website, custom web, Laravel, WordPress, Shopify, React and mobile app development.</p>', 0),
            $make($general, 'Where is your office located?', '<p>Our office is at C-203, C Block, Sector 63, Noida, Uttar Pradesh 201309. We work with clients across India and internationally.</p>', 1),
            $make($general, 'How quickly will you respond to my enquiry?', '<p>We reply to every enquiry within one business day — usually within a few hours. For urgent requirements, call or WhatsApp us.</p>', 2),
            $make($general, 'Do you sign NDAs and contracts?', '<p>Yes. We are happy to sign an NDA before discussing your project, and every engagement has a clear scope, timeline and deliverables.</p>', 3),
            $make($general, 'How do you report progress?', '<p>You get a dedicated point of contact, regular check-ins and a monthly report that connects our work to traffic, leads and revenue.</p>', 4),
            $make($general, 'Do you work with small businesses and startups?', '<p>Absolutely. We tailor plans to your stage and budget, starting with the activities most likely to produce results quickly.</p>', 5),
        ];

        $seoFaqs = [
            $make($seoCat, 'How long does SEO take to show results?', '<p>Most businesses see early movement in 6–12 weeks and meaningful growth in 4–6 months, depending on competition, website health and content.</p>', 0),
            $make($seoCat, 'Do you guarantee first-page rankings?', '<p>No ethical agency can guarantee rankings because search engines control them. We commit to transparent, guideline-compliant work and measurable KPIs.</p>', 1),
            $make($seoCat, 'What is included in your SEO packages?', '<p>Technical audit and fixes, keyword research, on-page optimisation, content, local SEO, link earning and monthly reporting — scoped to your goals.</p>', 2),
            $make($seoCat, 'Will SEO work for my business in {{location_name}}?', '<p>Yes — we research how customers in {{location_name}} search for your services and build pages, profiles and content around that local demand.</p>', 3),
        ];

        $devFaqs = [
            $make($devCat, 'How much does a website cost?', '<p>It depends on pages, features and integrations. Business websites typically start from a fixed package; custom applications are quoted after a short discovery call.</p>', 0),
            $make($devCat, 'How long does it take to build a website?', '<p>A typical business website takes 3–6 weeks. Custom web apps and mobile apps are planned in sprints with a timeline agreed upfront.</p>', 1),
            $make($devCat, 'Will I be able to update the website myself?', '<p>Yes. We build an easy admin panel and provide training so your team can update content, images and SEO without a developer.</p>', 2),
            $make($devCat, 'Do you provide maintenance after launch?', '<p>We offer maintenance plans covering updates, backups, security monitoring, performance and small enhancements.</p>', 3),
        ];

        foreach (Service::all() as $service) {
            $isDev = $service->category?->slug === 'development';
            // The location-variable FAQ is reserved for programmatic location pages.
            $service->syncFaqs(collect($isDev ? $devFaqs : array_slice($seoFaqs, 0, 3))->pluck('id')->all());
        }
        ServiceCategory::where('slug', 'digital-marketing')->first()?->syncFaqs(collect($seoFaqs)->take(3)->pluck('id')->all());
        ServiceCategory::where('slug', 'development')->first()?->syncFaqs(collect($devFaqs)->pluck('id')->all());
    }
}
