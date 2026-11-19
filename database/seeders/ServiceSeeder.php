<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Services and hubs mapped from the existing techalphonic.com sitemap. Slugs preserve the established
 * flat URLs (/laravel-development/, /ecommerce-seo/, /social-media/ …). All copy is editable in Admin.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $marketing = ServiceCategory::updateOrCreate(['slug' => 'digital-marketing'], [
            'name' => 'Digital Marketing', 'menu_label' => 'Digital Marketing', 'icon' => 'megaphone', 'sort_order' => 1,
            'h1' => 'Digital Marketing Services That Drive Measurable Growth',
            'short_description' => 'SEO, paid media, social and content programmes built around revenue — not vanity metrics.',
            'description' => '<p>We plan, execute and report on digital marketing the way a growth team would: clear goals, honest forecasts and monthly reporting that ties work to leads and sales.</p>',
            'status' => ContentStatus::Published, 'published_at' => now(),
        ]);
        $development = ServiceCategory::updateOrCreate(['slug' => 'development'], [
            'name' => 'Development', 'menu_label' => 'Development', 'icon' => 'code', 'sort_order' => 2,
            'h1' => 'Website, Software & App Development',
            'short_description' => 'Fast, secure, scalable websites and applications engineered for conversion and growth.',
            'description' => '<p>From marketing websites to custom platforms and mobile apps, our engineers build products that load fast, rank well and are easy for your team to manage.</p>',
            'status' => ContentStatus::Published, 'published_at' => now(),
        ]);

        $mkProcess = [
            ['title' => 'Audit & discovery', 'text' => 'We review your current performance, competitors and goals to find the biggest opportunities.'],
            ['title' => 'Strategy & roadmap', 'text' => 'A prioritised plan with clear KPIs, timelines and expected outcomes.'],
            ['title' => 'Execution', 'text' => 'Specialists implement, test and optimise — with weekly progress updates.'],
            ['title' => 'Reporting & growth', 'text' => 'Transparent monthly reports that connect our work to leads and revenue.'],
        ];
        $devProcess = [
            ['title' => 'Discovery & scope', 'text' => 'Workshops to define requirements, users, integrations and success metrics.'],
            ['title' => 'UX & design', 'text' => 'Wireframes and high-fidelity designs reviewed with you before any code.'],
            ['title' => 'Agile development', 'text' => 'Two-week sprints with staging previews so you see progress continuously.'],
            ['title' => 'Launch & support', 'text' => 'QA, performance tuning, secure deployment and ongoing maintenance.'],
        ];
        $mkWhy = [
            ['title' => 'Senior specialists', 'icon' => 'award', 'text' => 'Your account is handled by experienced practitioners, not interns.'],
            ['title' => 'Transparent reporting', 'icon' => 'chart', 'text' => 'Live dashboards and monthly reviews — you always know what you are paying for.'],
            ['title' => 'White-hat only', 'icon' => 'shield', 'text' => 'Sustainable, guideline-compliant techniques that protect your brand.'],
        ];

        $marketingServices = [
            ['search-engine-optimization', 'SEO Services', 'search', 'Rank higher on Google with technical, on-page and content SEO that brings qualified traffic and leads.', true, ['slug_base' => 'seo']],
            ['local-seo', 'Local SEO', 'map-pin', 'Win the map pack and “near me” searches with Google Business Profile optimisation and local citations.', true, ['slug_base' => 'local-seo']],
            ['ecommerce-seo', 'Ecommerce SEO', 'cart', 'Grow organic revenue with category, product and technical SEO for online stores.', true, []],
            ['technical-seo', 'Technical SEO', 'cpu', 'Fix crawlability, Core Web Vitals, indexing and site architecture issues that hold rankings back.', false, []],
            ['ai-seo-services', 'AI SEO', 'sparkles', 'Get cited in AI Overviews and answer engines with entity-focused, structured content.', false, []],
            ['on-page-seo', 'On-Page SEO', 'file-text', 'Optimise content, headings, internal links and metadata so every page targets the right intent.', false, []],
            ['off-page-seo-services', 'Off-Page SEO', 'link', 'Earn authoritative, relevant backlinks and brand mentions through digital PR and outreach.', false, []],
            ['app-store-seo', 'App Store SEO', 'smartphone', 'Increase installs with App Store and Google Play optimisation (ASO).', false, []],
            ['content-marketing', 'Content Marketing', 'pen', 'Research-led articles, guides and landing pages that rank, educate and convert.', true, []],
            ['social-media', 'Social Media Marketing', 'share', 'Build an engaged audience on Instagram, LinkedIn and Facebook with content and paid social.', true, []],
            ['pay-per-click', 'PPC Advertising', 'target', 'Google Ads and Meta Ads campaigns optimised for cost per lead and return on ad spend.', true, []],
            ['branding', 'Branding', 'palette', 'Brand strategy, identity and messaging that make you memorable and trusted.', false, []],
        ];
        $devServices = [
            ['website-development', 'Website Development', 'globe', 'Fast, SEO-ready, conversion-focused business websites that are easy to manage.', true],
            ['custom-web-development', 'Custom Web Development', 'layers', 'Bespoke web applications, portals and dashboards built around your workflows.', true],
            ['laravel-development', 'Laravel Development', 'code', 'Secure, scalable Laravel applications, APIs and SaaS platforms.', true],
            ['wordpress-development', 'WordPress Development', 'layout', 'Custom WordPress themes, plugins and performance-tuned business sites.', false],
            ['react-js-development', 'React JS Development', 'zap', 'High-performance React and Next.js front ends and single-page applications.', false],
            ['shopify-development', 'Shopify Development', 'store', 'Shopify stores, custom themes and apps that convert browsers into buyers.', false],
            ['app-development', 'App Development', 'smartphone', 'Native and cross-platform mobile apps for Android and iOS.', true],
            ['ui-ux', 'UI/UX Design', 'palette', 'Research-driven interface and experience design that users love.', false],
        ];

        $seo = null;
        foreach ($marketingServices as $i => [$slug, $name, $icon, $short, $featured, $tpl]) {
            $isSeoChild = str_contains($slug, 'seo') && $slug !== 'search-engine-optimization';
            $service = Service::updateOrCreate(['slug' => $slug], [
                'service_category_id' => $marketing->id,
                'parent_id' => $isSeoChild && $seo ? $seo->id : null,
                'name' => $name, 'icon' => $icon, 'short_description' => $short, 'is_featured' => $featured,
                'hero_heading' => $name.' That Delivers Real Business Results',
                'hero_description' => $short.' Strategy, execution and reporting from one accountable team.',
                'description' => $short,
                'content' => $this->copy($name, 'marketing'),
                'benefits' => [
                    ['title' => 'More qualified traffic', 'text' => 'Reach people actively searching for what you sell.', 'icon' => 'trending-up'],
                    ['title' => 'Lower cost per lead', 'text' => 'Compounding results that reduce dependence on paid ads.', 'icon' => 'banknote'],
                    ['title' => 'Measurable ROI', 'text' => 'Every activity is tracked against leads and revenue.', 'icon' => 'chart'],
                    ['title' => 'Brand authority', 'text' => 'Visibility that builds trust before the first call.', 'icon' => 'award'],
                ],
                'process' => $mkProcess,
                'cta_heading' => 'Get a free '.$name.' audit',
                'cta_description' => 'We will review your current performance and share quick wins — no obligation.',
                'landing_template' => array_merge($tpl, ['why_choose_us' => $mkWhy]),
                'status' => ContentStatus::Published, 'published_at' => now(), 'sort_order' => $i,
            ]);
            $seo ??= $slug === 'search-engine-optimization' ? $service : null;
            $service->syncSeo([
                'meta_title' => $name.' Company in Noida | Tech Alphonic',
                'focus_keyword' => strtolower($name),
            ]);
        }

        foreach ($devServices as $i => [$slug, $name, $icon, $short, $featured]) {
            $service = Service::updateOrCreate(['slug' => $slug], [
                'service_category_id' => $development->id,
                'name' => $name, 'icon' => $icon, 'short_description' => $short, 'is_featured' => $featured,
                'hero_heading' => $name.' Company in Noida',
                'hero_description' => $short.' Built by an experienced in-house team with transparent pricing.',
                'description' => $short,
                'content' => $this->copy($name, 'development'),
                'features' => [
                    ['title' => 'Mobile-first & responsive', 'icon' => 'smartphone', 'text' => 'Pixel-perfect on every screen size.'],
                    ['title' => 'SEO-ready architecture', 'icon' => 'search', 'text' => 'Clean URLs, schema and fast load times built in.'],
                    ['title' => 'Secure by default', 'icon' => 'shield', 'text' => 'OWASP best practices, backups and monitoring.'],
                    ['title' => 'Easy to manage', 'icon' => 'settings', 'text' => 'A clean admin panel your team can use without developers.'],
                ],
                'process' => $devProcess,
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Laravel', 'PHP 8', 'React', 'Next.js', 'Tailwind CSS', 'MySQL', 'WordPress', 'Shopify', 'Flutter', 'AWS']),
                'cta_heading' => 'Planning a '.strtolower($name).' project?',
                'cta_description' => 'Share your requirements and get a detailed proposal with timeline and cost.',
                'status' => ContentStatus::Published, 'published_at' => now(), 'sort_order' => $i,
            ]);
            $service->syncSeo([
                'meta_title' => $name.' Company in Noida | Tech Alphonic',
                'focus_keyword' => strtolower($name).' company',
            ]);
        }
    }

    private function copy(string $name, string $type): string
    {
        if ($type === 'development') {
            return "<h2>Why invest in professional {$name}?</h2>
<p>Your website or application is often the first interaction a customer has with your brand. A slow, outdated or hard-to-use experience costs you leads every day. Our {$name} team builds products that are fast, accessible, secure and designed around the actions you want visitors to take.</p>
<h2>What you get</h2>
<ul><li>A discovery workshop to align features with business goals</li><li>Responsive UI designed for your audience</li><li>Clean, documented code and a maintainable architecture</li><li>Performance optimisation for Core Web Vitals</li><li>Technical SEO foundations from day one</li><li>Post-launch support and maintenance plans</li></ul>
<h3>Built to scale with you</h3>
<p>We choose proven technologies and modular architecture so your platform can grow — new features, integrations and traffic — without a costly rebuild.</p>";
        }

        return "<h2>How our {$name} works</h2>
<p>Effective {$name} starts with understanding your customers: what they search for, which questions they ask and what convinces them to choose a provider. We combine that insight with data from your analytics to build a plan focused on qualified leads, not vanity metrics.</p>
<h2>What's included</h2>
<ul><li>In-depth audit and competitor benchmarking</li><li>Keyword and intent research mapped to your services</li><li>Implementation by specialists, not generalists</li><li>Conversion tracking for calls, forms and WhatsApp enquiries</li><li>Monthly reporting with clear next steps</li></ul>
<h3>Results you can measure</h3>
<p>We agree on KPIs at the start — traffic, rankings, enquiries and cost per lead — and report against them every month so you always know the return on your investment.</p>";
    }
}
