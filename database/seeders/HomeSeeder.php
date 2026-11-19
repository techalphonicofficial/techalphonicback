<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Models\HomeSection;
use Illuminate\Database\Seeder;

class HomeSeeder extends Seeder
{
    public function run(): void
    {
        HeroSlide::updateOrCreate(['sort_order' => 0], [
            'badge' => 'SEO · Marketing · Development — under one roof',
            'heading' => 'Grow Your Business With [[SEO, Marketing & Technology]]',
            'subheading' => 'Your Trusted IT Partner',
            'description' => 'We help ambitious businesses rank higher, generate more qualified leads and build fast, scalable websites and apps — all with one accountable team.',
            'cta_text' => 'Get a Free Consultation',
            'cta_url' => '#enquiry-popup',
            'secondary_cta_text' => 'Watch how we work',
            'secondary_cta_url' => null,
            'trust_text' => 'Trusted by 200+ clients across industries',
            'show_form' => true, // compact enquiry card on the right of the video banner
            'is_active' => true,
        ]);

        // key => [eyebrow, title, subtitle, content, visible]
        $sections = [
            'hero' => [null, null, null, [
                'stats' => [
                    // Figures as published on the old website (homepage counters / Our Work page).
                    ['value' => '7', 'suffix' => '+', 'label' => 'Years of experience'],
                    ['value' => '1500', 'suffix' => '+', 'label' => 'Projects completed'],
                    ['value' => '60', 'suffix' => '+', 'label' => 'Team members'],
                    ['value' => '100', 'suffix' => '%', 'label' => 'Client satisfaction'],
                ],
                'ticker' => ['Search Engine Optimization', 'Performance Marketing', 'Website Development', 'Laravel & Custom Software', 'Mobile Apps', 'Social Media', 'Branding & UI/UX', 'Ecommerce Growth'],
            ], true],
            'clients' => [null, 'Trusted by growing businesses', null, null, true], // logos attached by HomeMediaSeeder
            'services' => ['What we do', 'Everything you need to [[grow online]]', 'Strategy, marketing and engineering under one roof — so nothing falls between the cracks.', null, true],
            // Real “SEO Starter” plan from the Packages page.
            'promo' => ['90 days · visible results', 'SEO plans starting at [[₹9,999/month]]', 'Achieve visible growth and real performance improvements with our results-driven SEO strategy in just 90 days. No hidden charges, and a dedicated account manager on every plan.', [
                'points' => ['10 target keywords', 'On-page SEO optimisation', 'Google Business Profile setup', '5 blog articles every month', 'Technical SEO audit', 'Monthly performance report'],
                'button_text' => 'Get started with SEO',
                'note' => 'Month-to-month · Cancel with 30 days’ notice',
                'price_label' => 'SEO Starter', 'price_old' => null, 'price_new' => '₹9,999/mo',
            ], true],
            'about' => ['Why Tech Alphonic', 'One partner for [[marketing & technology]]', 'Most businesses juggle separate SEO, ads and development vendors. We bring them together.', [
                'text' => '<p>Our specialists in SEO, paid media, content, design and engineering work as one team with a single goal: measurable growth for your business. Clear KPIs, honest timelines and transparent reporting come standard.</p>',
                'points' => ['Dedicated account manager', 'In-house design & development', 'White-hat, guideline-compliant SEO', 'Monthly reports tied to leads & revenue'],
                'cta_text' => 'More about us', 'cta_url' => '/about-us/',
                'badge_value' => '7', 'badge_suffix' => '+', 'badge_label' => 'Years of digital growth experience',
            ], true],
            'showreel' => ['Inside Tech Alphonic', 'See how we turn ideas into [[measurable growth]]', 'A quick look at how our strategists, designers and engineers work together on every project.', [
                'stats' => [
                    ['value' => '94', 'suffix' => '%', 'label' => 'Client keywords improved'],
                    ['value' => '60–90', 'suffix' => '', 'label' => 'Days to first ranking gains'],
                ],
            ], true],
            'process' => ['How we work', 'A clear process, [[predictable results]]', null, ['steps' => [
                ['title' => 'Discover', 'text' => 'We learn your business, audience and goals, and audit what you have today.', 'icon' => 'search'],
                ['title' => 'Plan', 'text' => 'A prioritised roadmap with KPIs, timelines and transparent pricing.', 'icon' => 'target'],
                ['title' => 'Build & execute', 'text' => 'Specialists deliver in focused sprints with regular check-ins.', 'icon' => 'rocket'],
                ['title' => 'Measure & grow', 'text' => 'We report on results and keep optimising for growth.', 'icon' => 'trending-up'],
            ]], true],
            'industries' => ['Industries', 'Expertise across [[your industry]]', 'Strategies shaped around how your customers search, compare and buy.', null, true],
            'technologies' => ['Technology', 'Modern, proven [[technology stack]]', 'We choose tools for performance, security and long-term maintainability.', ['items' => [
                ['name' => 'Laravel', 'group' => 'Backend'], ['name' => 'PHP 8', 'group' => 'Backend'], ['name' => 'Node.js', 'group' => 'Backend'], ['name' => 'MySQL', 'group' => 'Backend'],
                ['name' => 'React', 'group' => 'Frontend'], ['name' => 'Next.js', 'group' => 'Frontend'], ['name' => 'Tailwind CSS', 'group' => 'Frontend'], ['name' => 'Alpine.js', 'group' => 'Frontend'],
                ['name' => 'Flutter', 'group' => 'Mobile'], ['name' => 'React Native', 'group' => 'Mobile'], ['name' => 'Kotlin', 'group' => 'Mobile'], ['name' => 'Swift', 'group' => 'Mobile'],
                ['name' => 'WordPress', 'group' => 'CMS & Commerce'], ['name' => 'Shopify', 'group' => 'CMS & Commerce'], ['name' => 'WooCommerce', 'group' => 'CMS & Commerce'],
                ['name' => 'Google Ads', 'group' => 'Marketing'], ['name' => 'GA4', 'group' => 'Marketing'], ['name' => 'Search Console', 'group' => 'Marketing'], ['name' => 'Meta Ads', 'group' => 'Marketing'], ['name' => 'Ahrefs', 'group' => 'Marketing'],
            ]], false],
            'portfolio' => ['Our work', 'Results that [[speak for themselves]]', 'A few recent projects and the growth they delivered.', null, true],
            'testimonials' => ['Testimonials', 'What our [[clients say]]', 'Long-term partnerships built on transparent work and real results.', null, true],
            'blogs' => ['Insights', 'Latest from our [[blog]]', 'Practical guides on SEO, marketing and development.', null, true],
            'faq' => ['FAQ', 'Frequently asked questions', null, null, true],
            'cta' => ['Let’s talk', 'Ready to grow your business online?', 'Get a free consultation and a clear, honest growth plan — no obligation.', ['button_text' => 'Get my free consultation', 'variant' => 'banner'], true],
            'stats' => [null, 'Our numbers', null, null, false],
            'enquiry' => ['Let’s talk', 'Ready to [[grow your business]]?', null, null, false],
        ];

        $order = 0;
        foreach ($sections as $key => [$eyebrow, $title, $subtitle, $content, $visible]) {
            $existing = HomeSection::where('key', $key)->first();
            HomeSection::updateOrCreate(['key' => $key], [
                'eyebrow' => $eyebrow, 'title' => $title, 'subtitle' => $subtitle,
                // keep media ids already attached (HomeMediaSeeder) when re-seeding
                'content' => $content === null ? $existing?->content : array_merge($existing?->content ?? [], $content),
                'sort_order' => $order++,
                'is_visible' => $visible,
            ]);
        }
    }
}
