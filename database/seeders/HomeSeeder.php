<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Models\HomeSection;
use Illuminate\Database\Seeder;

class HomeSeeder extends Seeder
{
    public function run(): void
    {
        $existingHero = HeroSlide::where('sort_order', 0)->first();

        HeroSlide::updateOrCreate(['sort_order' => 0], [
            'heading' => 'AI-Powered, Digital Marketing Company. [[Trends Defining the Future]]',
            'subheading' => 'Your Trusted IT Partner',
            'description' => 'In the modern digital age, marketing takes more than standard campaigns; it requires smart insights, automation, and data-driven calls. Tech Alphonic is an AI-driven digital marketing company focused on helping brands expand faster through advanced artificial intelligence and proven marketing methods. We fuse human creativity with AI tech to deliver sharper audience targeting, stronger engagement, and measurable business growth.',
            'cta_text' => 'Get a Free Consultation',
            'cta_url' => '#enquiry-popup',
            'secondary_cta_text' => 'Watch how we work',
            'secondary_cta_url' => null,
            'badge' => 'IT Solution Provider · 7+ Years of Trust',
            'trust_text' => '1500+ Projects Completed · 60+ Team Members · 100% Client Satisfaction',
            'image_id' => $existingHero?->image_id,
            'background_id' => $existingHero?->background_id ?? 3,
            'background_video_id' => $existingHero?->background_video_id ?? 2,
            'video_url' => $existingHero?->video_url,
            'show_form' => true,
            'is_active' => true,
        ]);

        // Preserve exact section keys, sort_order, is_visible, media bindings, and JSON shapes expected by HomeSections.tsx
        $sections = [
            // [key, order, visible, eyebrow, title, subtitle, content]
            ['hero', 0, true, null, null, null, [
                'stats' => [
                    ['value' => '7', 'suffix' => '+', 'label' => 'Years of experience'],
                    ['value' => '1500', 'suffix' => '+', 'label' => 'Projects completed'],
                    ['value' => '60', 'suffix' => '+', 'label' => 'Team members'],
                    ['value' => '100', 'suffix' => '%', 'label' => 'Client satisfaction'],
                ],
                'ticker' => [
                    'Search Engine Optimization',
                    'AI SEO (AEO & GEO)',
                    'Performance Marketing (PPC)',
                    'Social Media Marketing',
                    'Website Development',
                    'Laravel & Custom Software',
                    'Mobile App Development',
                    'Branding & UI/UX',
                    'Ecommerce Growth',
                ],
            ]],
            ['clients', 1, true, null, 'Our Valued Clients', null, [
                'items' => [
                    ['name' => 'AyurGrid', 'logo_id' => 57],
                    ['name' => 'Lawgical', 'logo_id' => 58],
                    ['name' => 'Kitchen Fix Solution', 'logo_id' => 59],
                    ['name' => 'Desi Guru', 'logo_id' => 60],
                    ['name' => 'Chardham Yatra', 'logo_id' => 61],
                    ['name' => 'Services Plus', 'logo_id' => 62],
                    ['name' => 'Com-Paint', 'logo_id' => 63],
                    ['name' => 'Enlive', 'logo_id' => 64],
                ],
            ]],
            ['services', 2, true, 'Our Expertise', 'Digital Marketing & Technology Services [[Built for Growth]]', 'From search visibility to custom software, Tech Alphonic brings every growth channel under one roof. We combine data, AI and hands-on experience to build strategies that fit your business, not a generic template.', [
                'image_id' => 7,
            ]],
            ['promo', 3, true, '90 days · visible results', 'SEO plans starting at [[₹9,999/month]]', 'Achieve visible growth and real performance improvements with our results-driven SEO strategy in just 90 days. No hidden charges, and a dedicated account manager on every plan.', [
                'points' => [
                    '10 target keywords',
                    'On-page SEO optimisation',
                    'Google Business Profile setup',
                    '5 blog articles every month',
                    'Technical SEO audit',
                    'Monthly performance report',
                ],
                'button_text' => 'Get started with SEO',
                'note' => 'Month-to-month · Cancel with 30 days’ notice',
                'price_label' => 'SEO Starter',
                'price_old' => null,
                'price_new' => '₹9,999/mo',
                'image_id' => 56,
            ]],
            ['about', 4, true, 'About Tech Alphonic', 'Accelerate Your Digital Growth with a [[Trusted Marketing Partner]]', 'We stay focused on your digital outcomes, not just traffic for traffic’s sake.', [
                'text' => '<p>At Tech Alphonic, we help businesses build a solid digital footprint and achieve measurable growth with our AI-powered digital marketing solutions. Our team brings together artificial intelligence, advanced analytics, and proven marketing strategies to create campaigns that bring in qualified traffic, good leads, and higher conversions.</p><p>We mix new ideas, data-led strategies, and industry know-how to support brands in getting noticed in a very competitive digital space. Whether you are a startup trying to get seen or an established business aiming to scale, our customized digital marketing solutions are built to deliver real business outcomes.</p>',
                'points' => [
                    'Expert Team — AI SEO, SMO, PPC & Full-Stack Developers',
                    'Responsive Support — Direct communication & agile execution',
                    'Proven Track Record — Data-led strategies & white-hat SEO',
                    'Transparent Reporting — Monthly reports tied to leads & ROI',
                ],
                'cta_text' => 'More About Us',
                'cta_url' => '/about-us/',
                'badge_value' => '7',
                'badge_suffix' => '+',
                'badge_label' => 'Years of Trust',
                'image_id' => 4,
            ]],
            ['showreel', 5, true, 'Why Choose an AI-Driven Agency', 'How AI Upshifts Your [[Digital Marketing Outcomes]]', 'The digital marketing space is changing fast — old-school guesswork is out. By blending AI tools with strategic marketing thinking, we help brands analyze customer behavior, map buyer journeys, and automate campaign performance.', [
                'stats' => [
                    ['value' => '1500', 'suffix' => '+', 'label' => 'Projects completed'],
                    ['value' => '100', 'suffix' => '%', 'label' => 'Client satisfaction'],
                ],
                'image_id' => 5,
                'video_id' => 2,
            ]],
            ['process', 6, true, 'Our Business Workflow', 'How an engagement moves from [[insight to growth]]', 'Our structured six-step business workflow ensures every campaign is data-driven, efficient, and focused on measurable outcomes.', [
                'steps' => [
                    ['title' => 'Business Analysis', 'text' => 'We evaluate your business model, objectives, target audience, and current digital footprint to establish a clear baseline for growth.', 'icon' => 'search'],
                    ['title' => 'Strategy Development', 'text' => 'Our specialists design a tailored digital marketing and technology roadmap with prioritized channels, deliverables, and KPIs.', 'icon' => 'target'],
                    ['title' => 'AI-Based Market Research', 'text' => 'Using AI insights and search intelligence, we analyze customer intent, high-converting keywords, competitor gaps, and market trends.', 'icon' => 'cpu'],
                    ['title' => 'Campaign Implementation', 'text' => 'We execute SEO, SMO, PPC, content, and web development initiatives with clean technical execution and conversion-focused design.', 'icon' => 'rocket'],
                    ['title' => 'Monitoring and Optimization', 'text' => 'We continuously track rankings, traffic, engagement, and conversion signals in real time to refine campaigns and maximize ROI.', 'icon' => 'trending-up'],
                    ['title' => 'Reporting and Growth Planning', 'text' => 'You receive transparent performance reports on traffic, leads, and ROI alongside actionable plans for the next phase of growth.', 'icon' => 'chart'],
                ],
            ]],
            ['industries', 7, true, 'Industries', 'Dominate Search in [[Your Industry]]', 'Every industry has unique buyer journeys, competitive landscapes, and search behaviors. We build custom strategies tailored to your vertical.', null],
            ['technologies', 8, false, 'Technology', 'Modern, proven [[technology stack]]', 'We choose tools for performance, security and long-term maintainability.', [
                'items' => [
                    ['name' => 'Laravel', 'group' => 'Backend'],
                    ['name' => 'PHP 8', 'group' => 'Backend'],
                    ['name' => 'Node.js', 'group' => 'Backend'],
                    ['name' => 'MySQL', 'group' => 'Backend'],
                    ['name' => 'React', 'group' => 'Frontend'],
                    ['name' => 'Next.js', 'group' => 'Frontend'],
                    ['name' => 'Tailwind CSS', 'group' => 'Frontend'],
                    ['name' => 'Alpine.js', 'group' => 'Frontend'],
                    ['name' => 'Flutter', 'group' => 'Mobile'],
                    ['name' => 'React Native', 'group' => 'Mobile'],
                    ['name' => 'Kotlin', 'group' => 'Mobile'],
                    ['name' => 'Swift', 'group' => 'Mobile'],
                    ['name' => 'WordPress', 'group' => 'CMS & Commerce'],
                    ['name' => 'Shopify', 'group' => 'CMS & Commerce'],
                    ['name' => 'WooCommerce', 'group' => 'CMS & Commerce'],
                    ['name' => 'Google Ads', 'group' => 'Marketing'],
                    ['name' => 'GA4', 'group' => 'Marketing'],
                    ['name' => 'Search Console', 'group' => 'Marketing'],
                    ['name' => 'Meta Ads', 'group' => 'Marketing'],
                    ['name' => 'Ahrefs', 'group' => 'Marketing'],
                ],
            ]],
            ['portfolio', 9, true, 'Our Work', 'Results that [[speak for themselves]]', 'Verified search rankings and digital growth delivered for our clients.', null],
            ['testimonials', 10, true, 'Client Reviews', 'What Our [[Clients Say]] About Us', 'Don’t just take our word for it — hear from businesses we have helped grow across India.', null],
            ['blogs', 11, true, 'Latest Insights', 'Latest Insights & [[Blogs]]', 'Explore our latest blogs, industry trends, web development tips, digital marketing strategies, and technology insights designed to help your business grow online and stay ahead in the digital world.', null],
            ['faq', 12, true, 'FAQ', 'Frequently Asked [[Questions]]', 'Everything you need to know about our AI-driven digital marketing and technology services.', null],
            ['cta', 13, true, 'Let’s Talk', 'Ready to Grow Your Business With the [[Best IT Partner]]?', 'Get a free consultation and a custom growth plan tailored to your goals — no obligation.', [
                'button_text' => 'Get a Free Consultation',
                'button_url' => '/contact/',
                'variant' => 'banner',
            ]],
            ['stats', 14, false, null, 'Our numbers', null, [
                'items' => [
                    ['value' => '7', 'suffix' => '+', 'label' => 'Years of Trust'],
                    ['value' => '1500', 'suffix' => '+', 'label' => 'Projects Completed'],
                    ['value' => '60', 'suffix' => '+', 'label' => 'Team Members'],
                    ['value' => '100', 'suffix' => '%', 'label' => 'Client Satisfaction'],
                ],
            ]],
            ['enquiry', 15, false, 'Let’s talk', 'Ready to [[grow your business]]?', null, null],
        ];

        $keepKeys = [];
        foreach ($sections as [$key, $order, $visible, $eyebrow, $title, $subtitle, $content]) {
            $keepKeys[] = $key;
            HomeSection::updateOrCreate(['key' => $key], [
                'eyebrow' => $eyebrow,
                'title' => $title,
                'subtitle' => $subtitle,
                'content' => $content,
                'is_visible' => $visible,
                'sort_order' => $order,
            ]);
        }
        HomeSection::whereNotIn('key', $keepKeys)->delete();
    }
}
