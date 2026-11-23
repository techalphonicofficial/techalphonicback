<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $dm = ServiceCategory::updateOrCreate(['slug' => 'digital-marketing'], [
            'name' => 'Digital Marketing',
            'menu_label' => 'Digital Marketing',
            'h1' => 'AI-Driven [[Digital Marketing Services]] in India',
            'short_description' => 'Exceptional Digital Marketing Agency & Company in India. With more than 7 years of expertise, we offer customized, data-driven digital marketing strategies tailored around your business goals to grow your online presence and maximize ROI.',
            'description' => <<<'HTML'
<h2>Exceptional Digital Marketing Agency &amp; Company in India</h2>
<p>With more than 7 years of expertise, Tech Alphonic is a full-service digital marketing agency in Noida, India helping businesses of all sizes increase their online presence and maximize ROI. We design specific, data-driven strategies around your business goals rather than one-size-fits-all templates.</p>
<h3>Key Service Highlights</h3>
<ul>
    <li><strong>Search Engine Optimization (SEO):</strong> Optimize your website for higher rankings, increased organic traffic, and qualified leads.</li>
    <li><strong>Social Media Optimization (SMO) &amp; SMM:</strong> Build community, engagement, and conversions across Instagram, Facebook, LinkedIn, YouTube, and X.</li>
    <li><strong>Pay-Per-Click (PPC) Advertising:</strong> High-ROI paid search, display, shopping, and remarketing campaigns on Google Ads and Meta Ads.</li>
    <li><strong>Content Marketing:</strong> Strategic blogs, website copy, landing pages, and visual content that build topical authority and convert visitors.</li>
    <li><strong>Website Design &amp; Development:</strong> Fast, responsive, SEO-ready websites engineered to convert traffic into leads and sales.</li>
    <li><strong>Email Marketing &amp; Online Reputation Management (ORM):</strong> Automated lifecycle sequences and proactive brand reputation protection.</li>
    <li><strong>Conversion Rate Optimization (CRO) &amp; App Store Optimization (ASO):</strong> Turning existing traffic into higher revenue and boosting mobile app downloads on Google Play and the App Store.</li>
</ul>
<h3>Why Choose Tech Alphonic for Digital Marketing?</h3>
<ul>
    <li><strong>Tailored Strategies:</strong> Customized marketing roadmaps designed specifically around your industry, audience, and revenue targets.</li>
    <li><strong>Data-Driven Results:</strong> Every campaign decision is backed by analytics, AI insights, and conversion tracking.</li>
    <li><strong>Full-Service Solutions:</strong> SEO, AI SEO, PPC, Social Media, Content, Branding, and Web/App Development under one roof.</li>
    <li><strong>Transparent Reporting:</strong> Detailed point-by-point monthly reports on keyword rankings, traffic, leads, and ROI.</li>
</ul>
HTML,
            'icon' => 'megaphone',
            'sort_order' => 0,
            'status' => ContentStatus::Published,
        ]);
        $dm->syncSeo([
            'meta_title' => 'Digital Marketing Services in Noida, India — SEO, PPC, Social Media | Tech Alphonic',
            'meta_description' => 'AI-driven digital marketing services by Tech Alphonic: SEO, AI SEO, Local SEO, E-Commerce SEO, PPC, Social Media, Content Marketing & Branding.',
            'robots_index' => true, 'include_in_sitemap' => true,
        ]);

        $dev = ServiceCategory::updateOrCreate(['slug' => 'development'], [
            'name' => 'Development',
            'menu_label' => 'Development',
            'h1' => 'Professional [[Web Development]] Services',
            'short_description' => 'We build fast, modern, and conversion-focused websites and mobile applications that grow your business, attract customers, and deliver real results.',
            'description' => <<<'HTML'
<h2>What We Build — Complete Web &amp; App Development Solutions</h2>
<p>From simple landing pages to complex enterprise portals, we deliver end-to-end development tailored to your business needs. A well-built website or app builds trust, drives sales, and establishes brand recognition.</p>
<ul>
    <li><strong>Custom Website Design:</strong> Pixel-perfect, brand-aligned UI/UX design crafted from scratch to captivate visitors and drive conversions.</li>
    <li><strong>E-Commerce Development:</strong> Full-featured online stores on WooCommerce, Shopify, or custom platforms with seamless payment gateway integration.</li>
    <li><strong>CMS &amp; WordPress Sites:</strong> Easy-to-manage websites built on WordPress, Webflow, or custom CMS so you can update content effortlessly.</li>
    <li><strong>Web App Development:</strong> Scalable SaaS platforms, portals, dashboards, and custom web applications using React, Vue, Node.js, and Laravel.</li>
    <li><strong>Landing Page Design:</strong> High-converting landing pages optimized for speed, SEO, and paid ad campaigns to maximize your ROI.</li>
    <li><strong>Maintenance &amp; Support:</strong> Ongoing updates, security patches, performance monitoring, backups, and technical support to keep your site running 24/7.</li>
</ul>
<h3>Why Businesses Trust Us — Built for Speed, Scale &amp; Conversions</h3>
<ul>
    <li><strong>Lightning-Fast Performance:</strong> Optimized for speed with 95+ Google PageSpeed scores — fast sites rank higher and convert better.</li>
    <li><strong>100% Mobile Responsive:</strong> Flawless experience across all devices — mobile, tablet, and desktop — tested on every screen size.</li>
    <li><strong>SEO-Ready Architecture:</strong> Built with clean semantic code, schema markup, fast load times, and SEO best practices from day one.</li>
    <li><strong>Secure &amp; Reliable:</strong> SSL certificates, security hardening, regular backups, and GDPR-ready code included in every project.</li>
</ul>
HTML,
            'icon' => 'code',
            'sort_order' => 1,
            'status' => ContentStatus::Published,
        ]);
        $dev->syncSeo([
            'meta_title' => 'Website, E-Commerce & Mobile App Development Services | Tech Alphonic',
            'meta_description' => 'Custom website development, Laravel, WordPress, React.js, Shopify e-commerce, mobile app development and UI/UX design services by Tech Alphonic.',
            'robots_index' => true, 'include_in_sitemap' => true,
        ]);
        // Keep original new website section structure on /development/ (no extra pricing PageBlock)
        $dev->blocks()->delete();

        $services = $this->getAuthenticServices($dm->id, $dev->id);

        foreach ($services as $i => $item) {
            $existing = Service::where('slug', $item['slug'])->first();

            $service = Service::updateOrCreate(['slug' => $item['slug']], [
                'service_category_id' => $item['category_id'],
                'name' => $item['name'],
                'menu_label' => $item['menu_label'],
                'hero_heading' => $item['h1'],
                'short_description' => $item['short_description'],
                'description' => $item['short_description'],
                'hero_description' => $item['hero_description'] ?? $item['short_description'],
                'icon' => $item['icon'],
                'is_featured' => $item['is_featured'],
                'sort_order' => $i,
                'status' => ContentStatus::Published,
                'published_at' => $existing?->published_at ?? now(),
                'content' => $item['content'],
                'features' => $item['features'],
                'benefits' => $item['benefits'],
                'process' => $item['process'],
                'technologies' => $item['technologies'],
                'pricing' => $item['pricing'] ?? null,
                'cta_heading' => $item['cta_heading'],
                'cta_description' => $item['cta_description'],
            ]);

            $service->syncSeo([
                'meta_title' => $item['meta_title'] ?? ($item['name'].' Services | Tech Alphonic'),
                'meta_description' => $item['meta_description'] ?? $item['short_description'],
                'focus_keyword' => strtolower($item['name']),
                'robots_index' => true,
                'robots_follow' => true,
                'include_in_sitemap' => true,
            ]);
        }
    }

    private function getAuthenticServices(int $dmId, int $devId): array
    {
        return [
            // 1. Search Engine Optimization (https://seashell-stork-114708.hostingersite.com/search-engine-optimization/)
            [
                'category_id' => $dmId,
                'slug' => 'search-engine-optimization',
                'name' => 'Search Engine Optimization',
                'menu_label' => 'SEO Services',
                'icon' => 'search',
                'is_featured' => true,
                'h1' => 'Dominate Search Rankings with [[Data-Driven SEO]]',
                'hero_subheading' => 'Search Engine Optimization Services',
                'short_description' => 'We help businesses rank higher on Google, drive targeted organic traffic, and convert visitors into paying customers with proven white-hat SEO strategies.',
                'content' => <<<'HTML'
<h2>What We Do — Complete SEO Solutions</h2>
<p>Every aspect of your search visibility covered — from technical foundations to authority link building and content strategy. We help businesses rank higher on Google, drive targeted organic traffic, and convert visitors into paying customers with proven white-hat SEO strategies.</p>
<p>We don’t guess — we use data, proven methodologies, and transparent reporting to grow your business organically with measurable ROI.</p>
HTML,
                'features' => [
                    ['title' => 'On-Page SEO', 'icon' => 'file', 'text' => 'Optimize meta tags, headings, content structure, internal linking, and keyword placement to maximize every page’s ranking potential on Google.'],
                    ['title' => 'Technical SEO', 'icon' => 'cpu', 'text' => 'Fix crawl errors, boost site speed, optimize Core Web Vitals, implement schema markup, XML sitemaps, and mobile-first indexing compliance.'],
                    ['title' => 'Off-Page SEO & Link Building', 'icon' => 'link', 'text' => 'Build high-authority backlinks through guest posting, digital PR, outreach campaigns, and brand mentions to boost your domain authority.'],
                    ['title' => 'Local SEO', 'icon' => 'map-pin', 'text' => 'Dominate Google Maps & local pack with Google My Business optimization, local citations, NAP consistency, and geo-targeted keywords.'],
                    ['title' => 'E-Commerce SEO', 'icon' => 'cart', 'text' => 'Optimize product pages, category structures, shopping feeds, and user intent keywords to drive buyers straight to your online store.'],
                    ['title' => 'Content & Keyword Strategy', 'icon' => 'pen', 'text' => 'Deep keyword research, competitor gap analysis, and SEO-optimized blog & landing page content that ranks and converts readers into leads.'],
                ],
                'benefits' => [
                    ['title' => '100% White-Hat SEO Practices', 'icon' => 'shield', 'text' => 'We strictly follow Google’s webmaster guidelines — zero black-hat shortcuts, ensuring long-term, penalty-free rankings that last.'],
                    ['title' => 'Dedicated SEO Account Manager', 'icon' => 'users', 'text' => 'One point of contact who knows your business inside-out and provides weekly updates, strategy calls, and priority support.'],
                    ['title' => 'Transparent Monthly Reports', 'icon' => 'chart', 'text' => 'Clear, jargon-free reports showing exact keyword rankings, organic traffic growth, backlinks built, and ROI metrics every month.'],
                    ['title' => 'AI-Powered SEO Research', 'icon' => 'sparkles', 'text' => 'We combine industry-leading tools (Ahrefs, SEMrush, Screaming Frog) with AI analysis to uncover opportunities your competitors miss.'],
                ],
                'process' => [
                    ['title' => 'SEO Audit', 'text' => 'Complete technical, on-page & competitor analysis to identify every opportunity and issue on your site.'],
                    ['title' => 'Keyword Research', 'text' => 'Identify high-intent, low-competition keywords your target customers are actively searching for.'],
                    ['title' => 'On-Page & Tech Fix', 'text' => 'Optimize site structure, speed, meta tags, content, schema, and fix all technical SEO bottlenecks.'],
                    ['title' => 'Track & Scale', 'text' => 'Build authority backlinks, monitor rankings weekly, and scale winning strategies for maximum ROI.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Ahrefs', 'SEMrush', 'Google Search Console', 'Google Analytics 4', 'Screaming Frog', 'Moz Pro', 'Surfer SEO', 'PageSpeed Insights', 'Yoast / RankMath', 'Google Tag Manager', 'Schema.org', 'BrightLocal']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Best for small businesses & startups',
                        'featured' => false,
                        'features' => implode("\n", [
                            '10 Target Keywords',
                            'On-Page SEO Optimization',
                            'Google My Business Setup',
                            'Monthly SEO Report',
                            'Basic Technical Audit',
                            'Link Building Campaign',
                            'Content Writing',
                            'Priority Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Growth',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'Best for growing businesses',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            '25 Target Keywords',
                            'Full On-Page Optimization',
                            'Technical SEO Fixes',
                            'Link Building (10 links/mo)',
                            '4 SEO Blog Articles/Month',
                            'Competitor Analysis',
                            'Detailed Monthly Reports',
                            'Dedicated Account Manager',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'Best for large businesses & e-commerce',
                        'featured' => false,
                        'features' => implode("\n", [
                            '60+ Target Keywords',
                            'Advanced Technical SEO',
                            'Link Building (25+ links/mo)',
                            '10 SEO Articles/Month',
                            'E-Commerce SEO',
                            'Schema & Rich Snippets',
                            'Weekly Ranking Reports',
                            'Priority 24/7 Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Rank #1 on [[Google]]?',
                'cta_description' => 'Get a free, no-obligation SEO audit of your website and discover how much organic traffic and revenue you’re leaving on the table.',
            ],

            // 2. Local SEO
            [
                'category_id' => $dmId,
                'slug' => 'local-seo',
                'name' => 'Local SEO',
                'menu_label' => 'Local SEO',
                'icon' => 'map-pin',
                'is_featured' => true,
                'h1' => 'Local SEO Services — [[Dominate Google Maps & Local Search]]',
                'hero_subheading' => 'Local Search & Google Business Profile Optimization',
                'short_description' => 'Get found easily by customers in your area with Google My Business (GMB) profile optimization, complete NAP consistency, and relevant local keyword targeting.',
                'content' => <<<'HTML'
<h2>Get Found Easily by Customers Searching in Your Area</h2>
<p>Local SEO is made for companies that wish to capture local clientele. Whether you operate a physical store, clinic, office, or location-based service business, our geo-targeting approach ensures your brand is seen prominently in regional searches and Google Maps.</p>
<p>We help local businesses build a strong digital footprint by optimizing your Google My Business (GMB) profile, maintaining complete NAP (Name, Address, Phone) consistency across directories, earning authentic customer reviews, and targeting high-intent local keywords so your business attracts more nearby customers and stays ahead of the competition.</p>
HTML,
                'features' => [
                    ['title' => 'Google Business Profile (GMB) Optimization', 'icon' => 'map-pin', 'text' => 'Complete setup, verification, category tuning, service listings, geo-tagged photos, weekly GMB posts, and Q&A optimization.'],
                    ['title' => 'Local Citations & NAP Consistency', 'icon' => 'list', 'text' => 'Building and auditing consistent Name, Address, and Phone listings across JustDial, Sulekha, IndiaMART, Bing Places, Apple Maps, and local directories.'],
                    ['title' => 'Hyper-Local Keyword Targeting', 'icon' => 'search', 'text' => 'Targeting high-converting “near me”, city, sector, and neighborhood search queries that drive direct phone calls and store visits.'],
                    ['title' => 'Location & Service-Area Landing Pages', 'icon' => 'layout', 'text' => 'Creating unique city and locality landing pages with LocalBusiness schema markup and embedded Google Maps.'],
                    ['title' => 'Review Generation & Reputation Management', 'icon' => 'star', 'text' => 'Structured workflows to earn more 5-star Google reviews and professional responses that boost local map pack rankings.'],
                    ['title' => 'Local Link Building & PR', 'icon' => 'link', 'text' => 'Acquiring backlinks from regional business associations, local news outlets, and community partners.'],
                ],
                'benefits' => [
                    ['title' => 'Top 3 Google Map Pack Visibility', 'icon' => 'map-pin', 'text' => 'Appear prominently when nearby customers search for your services on mobile and Google Maps.'],
                    ['title' => 'More Direct Calls & Walk-Ins', 'icon' => 'phone', 'text' => 'Turn high-intent local searchers into immediate phone calls, WhatsApp messages, and direction requests.'],
                    ['title' => 'Stronger Neighborhood Trust', 'icon' => 'shield', 'text' => 'Verified business profiles and consistent positive reviews make you the obvious local choice.'],
                    ['title' => 'Multi-Location Scalability', 'icon' => 'layers', 'text' => 'Centralized local SEO management for brands expanding across multiple cities or branches.'],
                ],
                'process' => [
                    ['title' => 'Local Presence & GMB Audit', 'text' => 'Auditing your Google Business Profile, existing directory citations, NAP accuracy, and local pack competitors.'],
                    ['title' => 'Geo-Keyword & Area Mapping', 'text' => 'Mapping every service you offer against the cities, pin codes, and neighborhoods you serve.'],
                    ['title' => 'GMB & On-Page Local Optimization', 'text' => 'Optimizing your Google Business Profile, local landing pages, LocalBusiness schema, and click-to-call elements.'],
                    ['title' => 'Citations, Reviews & Local Links', 'text' => 'Building authoritative local citations, launching review-generation campaigns, and acquiring regional backlinks.'],
                    ['title' => 'Call, Direction & Rank Tracking', 'text' => 'Monthly reporting on map pack rankings, GMB phone calls, website clicks, and direction requests.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Google Business Profile', 'Google Maps', 'BrightLocal', 'Google Search Console', 'GA4', 'Ahrefs', 'SEMrush', 'Schema.org LocalBusiness']),
                'cta_heading' => 'Ready to Dominate [[Google Maps & Local Search]]?',
                'cta_description' => 'Get a free Local SEO and Google Business Profile audit from our specialists.',
            ],

            // 3. Ecommerce SEO (https://seashell-stork-114708.hostingersite.com/ecommerce-seo/)
            [
                'category_id' => $dmId,
                'slug' => 'ecommerce-seo',
                'name' => 'Ecommerce SEO',
                'menu_label' => 'Ecommerce SEO',
                'icon' => 'cart',
                'is_featured' => true,
                'h1' => 'Turn Search Engines Into Your [[#1 Sales Channel]]',
                'hero_subheading' => 'Ecommerce SEO Services',
                'short_description' => 'We help online stores rank on page 1 for high-intent buyer keywords, drive organic traffic, and double revenue — without spending a single rupee more on paid ads.',
                'content' => <<<'HTML'
<h2>Why Most Online Stores Struggle With Organic Traffic</h2>
<p>Having great products isn’t enough if customers can’t find your store on Google. Your product pages sit on page 3–5 of Google while competitors capture 90% of the clicks and sales. Paid ads are eating your margins — the moment you pause your ad spend, traffic and sales drop to zero overnight.</p>
<p>Duplicate product descriptions, slow site speed, broken canonicals, and poor site architecture silently kill your rankings. Generic agencies optimize for vanity keywords with high search volume that bring browsers — not buyers ready to checkout. At TechAlphonic, we fix every technical and content bottleneck so your online store dominates organic search.</p>
HTML,
                'features' => [
                    ['title' => 'Buyer-Intent Keyword Research', 'icon' => 'search', 'text' => 'We identify high-converting transactional and commercial keywords your customers search right before making a purchase — mapped to every product, category, and blog page.'],
                    ['title' => 'Product Page SEO Optimization', 'icon' => 'cart', 'text' => 'Unique, persuasive product titles, meta descriptions, H1s, alt tags, and keyword-rich descriptions that rank on Google and convince visitors to click “Add to Cart”.'],
                    ['title' => 'Category & Collection Page SEO', 'icon' => 'layers', 'text' => 'Category pages are your highest-value SEO assets. We structure, optimize, and interlink them to rank for broad, high-volume buyer keywords in your niche.'],
                    ['title' => 'Technical Ecommerce SEO', 'icon' => 'cpu', 'text' => 'Fix faceted navigation issues, duplicate URLs, crawl budget waste, canonical tags, pagination, site speed, and Core Web Vitals for large product catalogs.'],
                    ['title' => 'Product Schema & Rich Snippets', 'icon' => 'code', 'text' => 'Display star ratings, prices, availability, and review counts directly in Google search results — increasing your click-through rate by up to 35%.'],
                    ['title' => 'Ecommerce Link Building', 'icon' => 'link', 'text' => 'Acquire high-authority backlinks from relevant retail blogs, product roundups, gift guides, and digital PR placements to boost your store’s domain authority.'],
                ],
                'benefits' => [
                    ['title' => 'Revenue-First Approach', 'icon' => 'chart', 'text' => 'We don’t chase vanity traffic. Every keyword, page optimization, and link we build is tied directly to product views, add-to-carts, and completed checkouts.'],
                    ['title' => 'Dedicated Ecommerce SEO Team', 'icon' => 'users', 'text' => 'Work directly with senior ecommerce SEO strategists, technical developers, and conversion copywriters who understand online retail inside and out.'],
                    ['title' => '100% Transparent Reporting', 'icon' => 'file', 'text' => 'Live dashboard + monthly reports showing keyword rankings, organic traffic, organic revenue, conversion rates, and exact work completed — no fluff.'],
                    ['title' => 'White-Hat, Long-Term Growth', 'icon' => 'shield', 'text' => '100% Google-compliant strategies that protect your brand and build compounding organic traffic that survives every core algorithm update.'],
                ],
                'process' => [
                    ['title' => 'Deep Store Audit', 'text' => 'Full technical, on-page, backlink & competitor audit of your online store to uncover quick wins and hidden issues.'],
                    ['title' => 'Keyword & Site Map', 'text' => 'Map high-intent buyer keywords to categories, subcategories, products, and blog topics for maximum coverage.'],
                    ['title' => 'On-Page & Tech Fixes', 'text' => 'Optimize pages, fix speed & crawl errors, add schema markup, and restructure internal linking across your catalog.'],
                    ['title' => 'Content & Link Growth', 'text' => 'Publish buying guides, build authority backlinks, track revenue in GA4, and scale winning categories monthly.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Shopify', 'WooCommerce', 'Magento', 'BigCommerce', 'Wix Ecommerce', 'Custom Stores', 'Google Search Console', 'GA4 Ecommerce', 'Ahrefs', 'SEMrush']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Best for small ecommerce stores just getting started with SEO.',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Up to 25 Product Pages',
                            'Keyword Research (50 Keywords)',
                            'On-Page SEO Optimization',
                            'Technical SEO Audit',
                            '5 Blog Posts / Month',
                            '10 Backlinks / Month',
                            'Monthly Report',
                            'Email Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Growth',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'Perfect for growing stores wanting aggressive organic growth.',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'Up to 100 Product Pages',
                            'Keyword Research (200 Keywords)',
                            'Advanced On-Page Optimization',
                            'Full Technical SEO Fix',
                            '12 Blog Posts / Month',
                            '25 Backlinks / Month',
                            'Schema Markup Implementation',
                            'Dedicated Account Manager',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'For large stores and brands wanting market dominance.',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Unlimited Product Pages',
                            'Comprehensive Keyword Strategy',
                            'Full Site Migration Support',
                            'CRO + SEO Combined Strategy',
                            '20+ Blog Posts / Month',
                            '50+ Backlinks / Month',
                            'Weekly Strategy Calls',
                            'Priority Support & Reporting',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Grow Your [[Online Store Sales]]?',
                'cta_description' => 'Get a free, no-obligation Ecommerce SEO audit worth ₹15,000. We’ll show you exactly why competitors are outranking you and how to beat them.',
            ],

            // 4. Technical SEO
            [
                'category_id' => $dmId,
                'slug' => 'technical-seo',
                'name' => 'Technical SEO',
                'menu_label' => 'Technical SEO',
                'icon' => 'cpu',
                'is_featured' => false,
                'h1' => 'Technical SEO Services — [[Speed, Crawlability & Core Web Vitals]]',
                'hero_subheading' => 'Enterprise Technical SEO Audits & Engineering Fixes',
                'short_description' => 'Our tech-savvy SEO experts refine key website elements including mobile responsiveness, page speed, Core Web Vitals, schema markup, robots.txt, llms.txt, XML sitemaps, and URL structure.',
                'content' => <<<'HTML'
<h2>Build a Rock-Solid Technical Foundation for Search &amp; AI Crawlers</h2>
<p>Even the best content cannot rank if search engines struggle to crawl, render, or index your pages. Our tech-savvy SEO experts refine key website elements, including mobile responsiveness, page speed, Core Web Vitals (LCP, INP, CLS), schema markup, robots.txt, llms.txt, XML sitemaps, and URL structure. This comprehensive optimization ensures your site meets all search engine requirements, improves overall performance, and delivers a smooth, fully compliant user experience.</p>
<p>Because Tech Alphonic combines full-stack web developers (Laravel, React/Next.js, WordPress, Shopify) with senior SEO strategists under one roof, we don’t just hand you an audit PDF — we implement the technical fixes directly in your codebase.</p>
HTML,
                'features' => [
                    ['title' => 'Core Web Vitals & Page Speed Optimization', 'icon' => 'bolt', 'text' => 'Optimizing Largest Contentful Paint (LCP), Interaction to Next Paint (INP), and Cumulative Layout Shift (CLS) across mobile and desktop.'],
                    ['title' => 'Crawlability & Indexation Architecture', 'icon' => 'search', 'text' => 'Resolving orphan pages, redirect chains, 404/soft-404 errors, duplicate content, canonical mismatches, and crawl budget waste.'],
                    ['title' => 'Structured Data & JSON-LD Schema', 'icon' => 'code', 'text' => 'Implementing and validating Organization, Service, Product, FAQ, Article, Breadcrumb, and LocalBusiness schema for rich SERP features.'],
                    ['title' => 'JavaScript & Next.js / React SSR SEO', 'icon' => 'cpu', 'text' => 'Ensuring client-rendered and hybrid web apps render clean HTML, metadata, and canonical tags for Googlebot and AI crawlers.'],
                    ['title' => 'Site Migrations & Redesign Protection', 'icon' => 'shield', 'text' => 'Full 301 redirect mapping, staging environment audits, and post-launch monitoring so you never lose rankings during a redesign.'],
                    ['title' => 'XML Sitemaps, Robots.txt & LLM.txt Optimization', 'icon' => 'file', 'text' => 'Configuring clean dynamic XML/HTML sitemaps, robots.txt directives, and llms.txt structure for traditional and generative search engines.'],
                ],
                'benefits' => [
                    ['title' => 'Faster Indexing & Higher Crawl Efficiency', 'icon' => 'bolt', 'text' => 'Search engines discover and rank your new and updated pages faster.'],
                    ['title' => '90+ PageSpeed & Superior Mobile UX', 'icon' => 'smartphone', 'text' => 'Faster loading times reduce bounce rates and directly lift conversion rates.'],
                    ['title' => 'Rich Snippets & AI Overview Eligibility', 'icon' => 'sparkles', 'text' => 'Validated schema markup unlocks rich results, FAQs, and generative search citations.'],
                    ['title' => 'Implemented by In-House Developers', 'icon' => 'code', 'text' => 'No back-and-forth delays — our engineering team deploys technical fixes directly.'],
                ],
                'process' => [
                    ['title' => 'Full Crawl & Log Analysis', 'text' => 'Crawling every URL with Screaming Frog, Ahrefs, and Google Search Console to surface all technical blockers.'],
                    ['title' => 'Core Web Vitals & Rendering Audit', 'text' => 'Analyzing asset loading, server TTFB, DOM size, JavaScript execution, and layout shifts.'],
                    ['title' => 'Prioritized Engineering Roadmap', 'text' => 'Sorting fixes by business impact vs. effort — critical indexation and speed issues first.'],
                    ['title' => 'Code-Level Implementation & QA', 'text' => 'Deploying schema, speed optimizations, canonicals, sitemaps, and redirects with staging verification.'],
                    ['title' => 'GSC Validation & Ongoing Monitoring', 'text' => 'Submitting fixes in Google Search Console and monitoring coverage, Core Web Vitals, and ranking gains.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Screaming Frog', 'Google Search Console', 'PageSpeed Insights', 'Lighthouse', 'Ahrefs Site Audit', 'SEMrush', 'Schema.org Validator', 'Cloudflare', 'Next.js SSR', 'Laravel', 'WordPress', 'Shopify']),
                'cta_heading' => 'Need a Deep [[Technical SEO Audit]]?',
                'cta_description' => 'Let our technical SEO engineers uncover and fix the hidden crawl, speed, and indexing issues holding your site back.',
            ],

            // 5. AI SEO Services (https://seashell-stork-114708.hostingersite.com/ai-seo-services/)
            [
                'category_id' => $dmId,
                'slug' => 'ai-seo-services',
                'name' => 'AI SEO Services',
                'menu_label' => 'AI SEO (AEO / GEO)',
                'icon' => 'sparkles',
                'is_featured' => true,
                'h1' => 'Best AI SEO Services in India — [[Rank on ChatGPT, Gemini & Google SGE]]',
                'hero_subheading' => 'AEO · GEO · LLM Optimization Services',
                'short_description' => 'AI SEO Services help businesses appear in AI-generated answers across Google AI Overviews (SGE), ChatGPT, Gemini, Perplexity, and Copilot through AEO, GEO, and LLM Optimization.',
                'content' => <<<'HTML'
<h2>What Are AI SEO Services and Why Do They Matter?</h2>
<p>Search behavior has changed. Users now ask full questions to AI assistants like <strong>ChatGPT</strong>, <strong>Google Gemini</strong>, <strong>Perplexity</strong>, <strong>Microsoft Copilot</strong>, and read <strong>Google AI Overviews (SGE)</strong> instead of clicking 10 blue links. If your brand isn’t optimized for AI engines, you lose visibility where modern buyers make decisions.</p>
<p>At Tech Alphonic, our AI SEO framework combines <strong>Answer Engine Optimization (AEO)</strong>, <strong>Generative Engine Optimization (GEO)</strong>, and <strong>LLM Optimization (LLMO)</strong> with deep structured data, entity authority, and conversational content so AI platforms cite and recommend your business first.</p>
HTML,
                'features' => [
                    ['title' => 'LLM Optimization (LLMO)', 'icon' => 'cpu', 'text' => 'Structure brand entities, authoritative citations, and llms.txt documentation so Large Language Models accurately understand and recommend your services.'],
                    ['title' => 'Answer Engine Optimization (AEO)', 'icon' => 'message', 'text' => 'Format concise, fact-rich Q&A blocks, definitions, and comparison tables that win direct answer boxes, voice search, and AI summaries.'],
                    ['title' => 'Generative Engine Optimization (GEO)', 'icon' => 'sparkles', 'text' => 'Optimize pages for Google AI Overviews (SGE) and Perplexity citations through original insights, statistics, and high-E-E-A-T topical hubs.'],
                    ['title' => 'ChatGPT SEO Services', 'icon' => 'search', 'text' => 'Ensure Bing indexation, third-party review presence, and authoritative listicle/directory mentions that power ChatGPT Search recommendations.'],
                    ['title' => 'Google Gemini & SGE SEO Services', 'icon' => 'globe', 'text' => 'Align Knowledge Graph signals, Google Business Profile data, and deep schema markup with Google’s Gemini-powered search results.'],
                    ['title' => 'Perplexity & Microsoft Copilot SEO', 'icon' => 'layers', 'text' => 'Optimize for citation-first answer engines and enterprise B2B discovery across Perplexity AI, Microsoft Bing, and Copilot web grounding.'],
                ],
                'benefits' => [
                    ['title' => 'First-Mover Advantage in AI Search', 'icon' => 'rocket', 'text' => 'Capture citations and brand mentions in AI assistants before competitors adapt their content strategy.'],
                    ['title' => 'Higher-Intent Referral Traffic', 'icon' => 'chart', 'text' => 'Visitors arriving from ChatGPT, Perplexity, and AI Overviews convert at higher rates because the AI has already pre-qualified your brand.'],
                    ['title' => 'Stronger E-E-A-T & Entity Authority', 'icon' => 'shield', 'text' => 'Entity and schema improvements simultaneously boost both traditional Google rankings and generative AI visibility.'],
                    ['title' => 'Unified Google + AI Search Strategy', 'icon' => 'layers', 'text' => 'One integrated roadmap covering classic organic SERPs, local packs, and generative AI platforms.'],
                ],
                'process' => [
                    ['title' => 'AI Visibility & Entity Audit', 'text' => 'Testing how ChatGPT, Gemini, Perplexity, and Google AI Overviews currently describe and cite your brand vs. competitors.'],
                    ['title' => 'Conversational Prompt & Intent Mapping', 'text' => 'Identifying the natural-language questions, comparisons, and prompts your buyers ask AI search engines.'],
                    ['title' => 'AEO/GEO Content & Schema Engineering', 'text' => 'Restructuring key pages with direct answer blocks, entity attributes, comparison data, and nested JSON-LD schema.'],
                    ['title' => 'Authority Citations & Digital PR', 'text' => 'Building third-party mentions, reviews, and high-authority backlinks that LLMs rely on for consensus verification.'],
                    ['title' => 'AI Citation & Organic Tracking', 'text' => 'Monitoring brand mentions across AI responses, AI Overview impressions, and referral traffic growth.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Google AI Overviews (SGE)', 'ChatGPT Search', 'Google Gemini', 'Perplexity AI', 'Microsoft Copilot', 'Schema.org JSON-LD', 'llms.txt', 'Bing Webmaster Tools', 'Google Search Console', 'Ahrefs', 'Surfer SEO']),
                'cta_heading' => 'Ready to Get Your Brand Recommended by [[ChatGPT, Gemini & Google AI]]?',
                'cta_description' => 'Request a free AI SEO & Generative Search visibility audit for your brand today.',
            ],

            // 6. On-Page SEO (https://seashell-stork-114708.hostingersite.com/on-page-seo/)
            [
                'category_id' => $dmId,
                'slug' => 'on-page-seo',
                'name' => 'On-Page SEO',
                'menu_label' => 'On-Page SEO',
                'icon' => 'file',
                'is_featured' => false,
                'h1' => 'On-Page SEO Services — [[Optimize Every Page to Rank & Convert]]',
                'hero_subheading' => 'Content, Metadata, Internal Linking & UX Optimization',
                'short_description' => 'Unlock the full ranking potential of your website with precision on-page optimization covering keyword research, title & meta tags, header hierarchy, content optimization, internal linking, and schema markup.',
                'content' => <<<'HTML'
<h2>Unlock the Full Potential of Your Website With Strategic On-Page SEO</h2>
<p>On-Page SEO is the foundation of search engine visibility. It tells Google and AI search engines what your page is about, who it helps, and why it deserves to rank above competing pages. At Tech Alphonic, we optimize every visible and behind-the-scenes element of your pages so they align with search intent, engage human readers, and convert traffic into enquiries.</p>
<p>From semantic keyword clustering and high-CTR meta tags to clean H1–H6 heading hierarchy, image alt optimization, internal link architecture, and JSON-LD schema markup, we turn underperforming pages into top-ranking organic assets.</p>
HTML,
                'features' => [
                    ['title' => 'Keyword Research & Intent Mapping', 'icon' => 'search', 'text' => 'Assigning primary, secondary, and semantic LSI keywords to each page to prevent keyword cannibalization and match buyer intent.'],
                    ['title' => 'Title Tags, Meta Descriptions & H1–H6 Hierarchy', 'icon' => 'file', 'text' => 'Crafting high-CTR meta titles and descriptions alongside a clean, logical H1–H3 heading outline.'],
                    ['title' => 'Content Optimization & E-E-A-T Enhancement', 'icon' => 'pen', 'text' => 'Enriching page copy with comprehensive answers, topical depth, clear CTAs, and helpful formatting.'],
                    ['title' => 'Strategic Internal Linking & Anchor Architecture', 'icon' => 'link', 'text' => 'Building topic clusters and contextual internal links that distribute authority to your highest-value money pages.'],
                    ['title' => 'Image SEO & Multimedia Optimization', 'icon' => 'image', 'text' => 'Compressing images into next-gen WebP/AVIF formats with descriptive filenames and keyword-relevant alt attributes.'],
                    ['title' => 'URL Structure & Page-Level Schema Markup', 'icon' => 'code', 'text' => 'Clean, readable URLs paired with Service, FAQ, and Breadcrumb JSON-LD schema markup.'],
                ],
                'benefits' => [
                    ['title' => 'Higher Organic Click-Through Rates (CTR)', 'icon' => 'chart', 'text' => 'Compelling meta titles, descriptions, and rich FAQ snippets win more clicks from every impression.'],
                    ['title' => 'Immediate Ranking Gains for Existing Pages', 'icon' => 'rocket', 'text' => 'Pages stuck on page 2 or bottom of page 1 often jump into the top 3 after comprehensive on-page tuning.'],
                    ['title' => 'Lower Bounce Rate & Longer Dwell Time', 'icon' => 'users', 'text' => 'Clear heading hierarchy and intent-matched copy keep visitors engaged and moving toward your enquiry form.'],
                    ['title' => 'Zero Keyword Cannibalization', 'icon' => 'layers', 'text' => 'Every page has a distinct target topic and role inside your site architecture.'],
                ],
                'process' => [
                    ['title' => 'Page-by-Page Content & SERP Audit', 'text' => 'Analyzing your existing pages against top-ranking SERP competitors for structure, depth, and intent.'],
                    ['title' => 'Keyword & Topic Cluster Map', 'text' => 'Mapping target keywords and semantic entities to every core service, category, and blog URL.'],
                    ['title' => 'Metadata, Heading & Copy Optimization', 'text' => 'Rewriting titles, meta descriptions, headings, and body sections for clarity, relevance, and conversions.'],
                    ['title' => 'Internal Links, Images & Schema Deployment', 'text' => 'Adding contextual internal links, image alt tags, and page-specific JSON-LD schema.'],
                    ['title' => 'GSC Re-Indexing & CTR Tracking', 'text' => 'Requesting indexing in Google Search Console and tracking impression, position, and CTR improvements.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Surfer SEO', 'Google Search Console', 'Ahrefs', 'SEMrush', 'Screaming Frog', 'RankMath', 'Yoast SEO', 'Schema.org']),
                'cta_heading' => 'Want Every Page on Your Site [[Optimized for Page 1]]?',
                'cta_description' => 'Get a free On-Page SEO audit of your top landing pages from our SEO specialists.',
            ],

            // 7. Off-Page SEO (https://seashell-stork-114708.hostingersite.com/off-page-seo-services/)
            [
                'category_id' => $dmId,
                'slug' => 'off-page-seo-services',
                'name' => 'Off-Page SEO',
                'menu_label' => 'Off-Page SEO',
                'icon' => 'link',
                'is_featured' => false,
                'h1' => 'Off-Page SEO & Link Building Services — [[Build Lasting Domain Authority]]',
                'hero_subheading' => 'White-Hat Link Building, Digital PR & Brand Citations',
                'short_description' => 'Strengthen your website’s domain authority and search credibility with editorial backlinks, guest posting, digital PR, local citations, and brand mention campaigns.',
                'content' => <<<'HTML'
<h2>Earn the Authority &amp; Trust Signals That Push Competitive Keywords to #1</h2>
<p>While On-Page and Technical SEO tell search engines what your website is about, Off-Page SEO proves to Google and AI engines that your brand is a trusted, authoritative leader worth ranking at the top. At Tech Alphonic, we execute 100% white-hat, manual outreach link-building and digital PR campaigns — never spammy PBNs or automated link farms.</p>
<p>Every backlink opportunity is vetted across four strict criteria: <strong>Topical Relevance</strong>, <strong>Real Organic Traffic</strong>, <strong>Domain Authority &amp; Trust</strong>, and <strong>Editorial Placement Quality</strong>.</p>
HTML,
                'features' => [
                    ['title' => 'Contextual Guest Posting & Editorial Outreach', 'icon' => 'pen', 'text' => 'Publishing well-researched, niche-relevant articles on real, traffic-generating industry websites with natural contextual backlinks.'],
                    ['title' => 'Digital PR & Brand Mention Campaigns', 'icon' => 'megaphone', 'text' => 'Earning authoritative media mentions, founder quotes, and unlinked-brand-mention conversions on trusted publications.'],
                    ['title' => 'Competitor Backlink Gap Acquisition', 'icon' => 'search', 'text' => 'Reverse-engineering the exact authoritative domains linking to your top-ranking competitors and securing placements for your brand.'],
                    ['title' => 'Business Directories & Local Citations', 'icon' => 'map-pin', 'text' => 'Building high-trust national, industry, and local directory listings with consistent NAP details.'],
                    ['title' => 'Resource Page & Broken Link Building', 'icon' => 'link', 'text' => 'Replacing dead links and adding your guides to curated industry resource pages.'],
                    ['title' => 'Toxic Backlink Audit & Disavow Protection', 'icon' => 'shield', 'text' => 'Auditing your backlink profile to identify and disavow harmful spam links that could trigger algorithmic suppression.'],
                ],
                'benefits' => [
                    ['title' => 'Higher Domain Rating (DR) & Authority', 'icon' => 'chart', 'text' => 'Steady growth in domain authority that lifts rankings across your entire website.'],
                    ['title' => '100% Manual White-Hat Outreach', 'icon' => 'shield', 'text' => 'Real editorial placements on genuine websites — safe from Google link-spam penalties.'],
                    ['title' => 'Referral Traffic & Brand Credibility', 'icon' => 'globe', 'text' => 'Placements on respected industry portals bring direct referral visitors and strengthen LLM/AI brand consensus.'],
                    ['title' => 'Transparent Live Link Reporting', 'icon' => 'file', 'text' => 'Every month you receive a clear report listing every live backlink URL, anchor text, and domain metric.'],
                ],
                'process' => [
                    ['title' => 'Backlink Profile & Competitor Gap Audit', 'text' => 'Analyzing your current anchor text distribution, referring domains, and authority gap against top SERP competitors.'],
                    ['title' => 'Prospect Vetting & Outreach List', 'text' => 'Filtering target publications by topical relevance, organic traffic, DR/DA, and editorial standards.'],
                    ['title' => 'Content Creation & Manual Pitching', 'text' => 'Crafting publication-grade articles and pitching editors for contextual placement.'],
                    ['title' => 'Live Link QA & Indexation Monitoring', 'text' => 'Verifying every live placement, anchor attribute, and Google indexation status.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Ahrefs', 'SEMrush', 'Moz Link Explorer', 'Majestic', 'Google Search Console', 'Hunter.io', 'Google Alerts']),
                'cta_heading' => 'Ready to Build [[High-Authority Backlinks]] That Move the Needle?',
                'cta_description' => 'Get a free backlink gap analysis comparing your domain against your top 3 organic competitors.',
            ],

            // 8. App Store SEO (ASO) (https://seashell-stork-114708.hostingersite.com/app-store-seo/)
            [
                'category_id' => $dmId,
                'slug' => 'app-store-seo',
                'name' => 'App Store Optimization',
                'menu_label' => 'App Store SEO (ASO)',
                'icon' => 'smartphone',
                'is_featured' => false,
                'h1' => 'Skyrocket Your App Downloads with [[Expert ASO]]',
                'hero_subheading' => 'App Store Optimization Services',
                'short_description' => 'We help your mobile app rank at the top of Google Play & Apple App Store — driving organic downloads, higher visibility, and lower user acquisition cost.',
                'content' => <<<'HTML'
<h2>Complete ASO Solutions — Everything Your App Needs to Rank #1</h2>
<p>Over 65% of all app downloads come directly from searches inside the Google Play Store and Apple App Store. From keyword intelligence to visual asset optimization — we cover every factor that drives app store visibility and conversions.</p>
<p>Our ASO team optimizes your app across Google Play Store, Apple App Store, Samsung Galaxy Store, Amazon Appstore, Huawei AppGallery, and Xiaomi GetApps so you capture high-intent mobile users organically.</p>
HTML,
                'features' => [
                    ['title' => 'Keyword Research & Optimization', 'icon' => 'search', 'text' => 'Deep analysis of high-volume, low-competition keywords for your app title, subtitle, short description, and long description to maximize search visibility.'],
                    ['title' => 'Visual & Creative Optimization', 'icon' => 'image', 'text' => 'A/B tested app icons, high-converting screenshots, and compelling preview videos that boost your tap-to-install conversion rate by up to 40%.'],
                    ['title' => 'Ratings & Review Management', 'icon' => 'star', 'text' => 'Strategic in-app review prompts, sentiment analysis, and professional response management to maintain a 4.5★+ rating and build user trust.'],
                    ['title' => 'App Localization (Global ASO)', 'icon' => 'globe', 'text' => 'Translate and culturally adapt your app listing across 30+ languages and regions to unlock massive international download growth.'],
                    ['title' => 'A/B Testing & Conversion CRO', 'icon' => 'sliders', 'text' => 'Data-driven store listing experiments on Google Play and Product Page Optimization on App Store to continuously improve install rates.'],
                    ['title' => 'Competitor & Analytics Tracking', 'icon' => 'chart', 'text' => 'Real-time keyword ranking tracking, competitor movement alerts, and monthly ASO performance reports with clear ROI metrics.'],
                ],
                'benefits' => [
                    ['title' => 'Up to 300% Avg. Download Growth', 'icon' => 'rocket', 'text' => 'Capture high-intent users actively searching for apps in your category every day.'],
                    ['title' => 'Lower User Acquisition Cost', 'icon' => 'chart', 'text' => 'Higher store-listing conversion rates make both your organic traffic and paid app install ads cheaper.'],
                    ['title' => '4.8★ Avg. Client App Rating', 'icon' => 'star', 'text' => 'Clear screenshots and structured review prompts attract and retain satisfied users.'],
                    ['title' => 'All Major App Stores Covered', 'icon' => 'smartphone', 'text' => 'Complete optimization across Google Play Store, Apple App Store, Samsung Galaxy Store, Amazon Appstore, Huawei AppGallery, and Xiaomi GetApps.'],
                ],
                'process' => [
                    ['title' => 'App & Market Audit', 'text' => 'Deep audit of your current app listing, keyword rankings, conversion rates, and top 10 competitors.'],
                    ['title' => 'Keyword & Metadata Strategy', 'text' => 'Build a high-impact keyword matrix and optimize title, subtitle, keyword field, and descriptions.'],
                    ['title' => 'Visual Redesign & A/B Tests', 'text' => 'Design conversion-focused icons, screenshots & videos and run store listing experiments.'],
                    ['title' => 'Monitor, Iterate & Scale', 'text' => 'Track rankings daily, adapt to algorithm updates, manage reviews, and expand to new locales.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Google Play Store', 'Apple App Store', 'Samsung Galaxy Store', 'Amazon Appstore', 'Huawei AppGallery', 'Xiaomi GetApps', 'AppTweak', 'Sensor Tower']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Basic ASO',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Keyword Research (50 Keywords)',
                            'Title & Description Optimization',
                            '1 Platform (Play Store or App Store)',
                            'Screenshot Design (5 Images)',
                            'Monthly Ranking Report',
                            'Competitor Analysis (3 Apps)',
                            'A/B Testing',
                            'App Localization',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Professional ASO',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'Professional ASO',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'Keyword Research (150 Keywords)',
                            'Full Metadata Optimization',
                            'Both Platforms (Play + App Store)',
                            'Screenshot + Icon Design (10 Images)',
                            'Weekly Ranking Reports',
                            'Competitor Analysis (10 Apps)',
                            'A/B Testing (2 Experiments/Month)',
                            'Review Response Management',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'Advanced ASO',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Unlimited Keyword Research',
                            'Full Metadata + Off-Page ASO',
                            'All Platforms + Amazon Appstore',
                            'Full Visual Suite + Preview Video',
                            'Real-Time Dashboard Access',
                            'Unlimited Competitor Analysis',
                            'Unlimited A/B Testing',
                            'App Localization (5 Languages)',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Dominate the [[App Stores]]?',
                'cta_description' => 'Get a free, in-depth ASO audit of your app and discover untapped keyword & conversion opportunities today.',
            ],

            // 9. Content Marketing (https://seashell-stork-114708.hostingersite.com/content-marketing/)
            [
                'category_id' => $dmId,
                'slug' => 'content-marketing',
                'name' => 'Content Marketing',
                'menu_label' => 'Content Marketing',
                'icon' => 'pen',
                'is_featured' => false,
                'h1' => 'Content That Educates, [[Engages & Converts]]',
                'hero_subheading' => 'Strategic Content Marketing & SEO Copywriting Services',
                'short_description' => 'Turn your brand into an industry authority with data-driven content marketing — SEO blog writing, website copy, social media content, email campaigns, eBooks, and video scripts.',
                'content' => <<<'HTML'
<h2>Turn Your Expertise Into a Compounding Organic Traffic &amp; Lead Engine</h2>
<p>In a noisy digital landscape, generic filler articles don’t rank and don’t sell. Great content marketing answers the exact questions your buyers ask at every stage of their decision journey — earning their trust before they ever speak to your sales team.</p>
<p>At Tech Alphonic, our in-house content strategists, subject-matter writers, and SEO editors pair search-intent data with persuasive human storytelling to produce blog hubs, high-converting landing pages, case studies, email sequences, and visual assets that build lasting brand authority.</p>
HTML,
                'features' => [
                    ['title' => 'SEO Blog Writing & Articles', 'icon' => 'pen', 'text' => 'Long-form, keyword-optimized blog articles and pillar-cluster hubs engineered to rank on Google and earn AI Overview citations.'],
                    ['title' => 'Website & Landing Page Copywriting', 'icon' => 'layout', 'text' => 'Persuasive homepage, service-page, and ad-landing-page copy structured around buyer pain points, proof, and clear CTAs.'],
                    ['title' => 'Social Media Content Creation', 'icon' => 'share', 'text' => 'Scroll-stopping captions, carousel copy, and content calendars tailored for Instagram, LinkedIn, Facebook, and X.'],
                    ['title' => 'Email Marketing Campaigns', 'icon' => 'mail', 'text' => 'Automated welcome, lead-nurturing, and promotional email newsletters that keep your brand top-of-mind and drive repeat conversions.'],
                    ['title' => 'eBooks, Whitepapers & Case Studies', 'icon' => 'file', 'text' => 'In-depth B2B case studies, downloadable eBooks, and industry reports that capture gated leads and accelerate enterprise sales.'],
                    ['title' => 'Video Scripts & Infographics', 'icon' => 'video', 'text' => 'Engaging scripts for YouTube, Instagram Reels, and visual infographics that repurpose your core insights across every channel.'],
                ],
                'benefits' => [
                    ['title' => '100% Original, Human-Edited & E-E-A-T Compliant', 'icon' => 'shield', 'text' => 'Every piece is researched, fact-checked, and edited by experienced writers to reflect your authentic brand voice.'],
                    ['title' => 'Compounding Organic Search Visibility', 'icon' => 'chart', 'text' => 'Well-structured evergreen articles continue bringing in qualified prospects month after month.'],
                    ['title' => 'Higher Conversion Rates Across Funnels', 'icon' => 'rocket', 'text' => 'Clear value propositions and objection-handling copy turn more passive visitors into enquiries.'],
                    ['title' => 'Full Editorial Calendar & CMS Publishing', 'icon' => 'layers', 'text' => 'We handle topic research, writing, graphics, internal linking, and direct publishing to your CMS.'],
                ],
                'process' => [
                    ['title' => 'Research & Audit', 'text' => 'Auditing your existing content, ideal customer personas, tone of voice, and competitor content gaps.'],
                    ['title' => 'Content Strategy', 'text' => 'Mapping target keywords, topic clusters, and a 90-day editorial calendar aligned with your sales funnel.'],
                    ['title' => 'Creation & SEO', 'text' => 'Drafting, fact-checking, optimizing with NLP/Surfer guidelines, and adding schema and internal links.'],
                    ['title' => 'Publish & Promote', 'text' => 'Publishing on your CMS and distributing across social media, email newsletters, and outreach channels.'],
                    ['title' => 'Analyze & Scale', 'text' => 'Tracking rankings, organic traffic, engagement, and assisted leads to scale your top-performing topics.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Surfer SEO', 'Ahrefs', 'SEMrush', 'Google Search Console', 'Grammarly Business', 'Hemingway', 'WordPress', 'Figma', 'Canva Pro', 'Mailchimp', 'HubSpot', 'GA4']),
                'cta_heading' => 'Ready to Build a [[Content Engine]] That Ranks & Converts?',
                'cta_description' => 'Talk to our content strategists for a free content gap audit and 90-day editorial roadmap.',
            ],

            // 10. Social Media Marketing (https://seashell-stork-114708.hostingersite.com/social-media/)
            [
                'category_id' => $dmId,
                'slug' => 'social-media',
                'name' => 'Social Media Marketing',
                'menu_label' => 'Social Media (SMM)',
                'icon' => 'share',
                'is_featured' => true,
                'h1' => 'Turn Followers Into [[Loyal Customers]]',
                'hero_subheading' => 'Social Media Marketing Agency',
                'short_description' => 'We create scroll-stopping content, build engaged communities, and run high-ROI social ad campaigns across Instagram, Facebook, LinkedIn, YouTube & X.',
                'content' => <<<'HTML'
<h2>Everything You Need to Dominate Social Media</h2>
<p>From daily content creation to high-converting paid ad campaigns — we handle your entire social media presence end-to-end across <strong>Instagram</strong>, <strong>Facebook</strong>, <strong>LinkedIn</strong>, <strong>YouTube</strong>, <strong>X (Twitter)</strong>, and <strong>Pinterest</strong>.</p>
<p>We don’t just chase vanity likes — we focus on real followers, genuine engagement, qualified leads, and measurable revenue growth for your brand.</p>
HTML,
                'features' => [
                    ['title' => 'Social Media Strategy', 'icon' => 'layers', 'text' => 'Custom data-driven roadmap tailored to your brand voice, target audience, competitor analysis, and monthly growth KPIs.'],
                    ['title' => 'Content & Reels Creation', 'icon' => 'video', 'text' => 'Eye-catching graphics, carousels, stories, and viral short-form videos (Reels & Shorts) designed to stop the scroll and boost engagement.'],
                    ['title' => 'Paid Social Advertising', 'icon' => 'chart', 'text' => 'Laser-targeted Meta Ads, Instagram Ads, and LinkedIn Ads campaigns optimized for leads, website traffic, and e-commerce sales.'],
                    ['title' => 'Community Management', 'icon' => 'users', 'text' => 'Active comment replies, DM management, audience engagement, and reputation monitoring to build loyal brand advocates.'],
                    ['title' => 'Influencer Marketing', 'icon' => 'award', 'text' => 'Partner with vetted micro and macro influencers in your niche to amplify brand awareness and reach ready-to-buy audiences.'],
                    ['title' => 'Analytics & Monthly Reports', 'icon' => 'file', 'text' => 'Transparent monthly dashboards tracking reach, engagement rate, follower growth, ad ROAS, and leads generated.'],
                ],
                'benefits' => [
                    ['title' => 'Consistent Brand Identity', 'icon' => 'sparkles', 'text' => 'Cohesive visual aesthetic, tone of voice, and posting schedule across all 6 major social platforms.'],
                    ['title' => 'Viral Reels & Short-Form Video', 'icon' => 'video', 'text' => 'Hook-driven Reels and YouTube Shorts engineered to capture algorithmic organic reach.'],
                    ['title' => 'Full-Funnel Paid Social ROI', 'icon' => 'chart', 'text' => 'Retargeting and lookalike campaigns that turn social engagement into direct leads and sales.'],
                    ['title' => 'Dedicated Social Media Team', 'icon' => 'users', 'text' => 'Your own strategist, graphic designer, video editor, and ad manager working together on your brand.'],
                ],
                'process' => [
                    ['title' => 'Brand & Audit', 'text' => 'Deep dive into your brand, audience personas, current profiles, and top competitors.'],
                    ['title' => 'Content Calendar', 'text' => 'Plan a 30-day content calendar with posts, reels, stories, captions, and hashtag strategy.'],
                    ['title' => 'Create & Publish', 'text' => 'Design visuals, edit videos, get your approval, and schedule posts at peak engagement times.'],
                    ['title' => 'Analyze & Scale', 'text' => 'Track performance metrics, optimize ad spend, and double down on top-performing content.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Instagram', 'Facebook', 'LinkedIn', 'YouTube', 'X (Twitter)', 'Pinterest', 'Meta Ads Manager', 'Adobe Premiere Pro', 'After Effects', 'Figma', 'Canva Pro']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'For Small Businesses',
                        'featured' => false,
                        'features' => implode("\n", [
                            '2 Social Media Platforms',
                            '12 Posts Per Month',
                            'Basic Graphic Design',
                            'Page Setup & Optimization',
                            'Monthly Performance Report',
                            'Hashtag Research',
                            'Paid Ad Management',
                            'Reel / Video Creation',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Growth',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'For Growing Brands',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            '4 Social Media Platforms',
                            '24 Posts Per Month',
                            'Premium Graphic Design',
                            '4 Reels / Short Videos',
                            'Facebook & Instagram Ads',
                            'Community Management',
                            'Bi-Weekly Reports',
                            'Competitor Analysis',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'For Large Businesses',
                        'featured' => false,
                        'features' => implode("\n", [
                            'All 6 Social Platforms',
                            '40+ Posts Per Month',
                            '8 Reels + 2 Professional Videos',
                            'Full Paid Ad Management',
                            'Influencer Outreach',
                            '24/7 Community Management',
                            'Weekly Reports + Strategy Calls',
                            'Dedicated Account Manager',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Grow Your Brand on [[Social Media]]?',
                'cta_description' => 'Get a free social media audit and custom 30-day growth strategy from our experts — no obligation.',
            ],

            // 11. Pay Per Click (PPC) (https://seashell-stork-114708.hostingersite.com/pay-per-click/)
            [
                'category_id' => $dmId,
                'slug' => 'pay-per-click',
                'name' => 'Pay Per Click Advertising',
                'menu_label' => 'PPC / Google Ads',
                'icon' => 'bolt',
                'is_featured' => true,
                'h1' => 'Stop Wasting Ad Spend. [[Start Getting Real ROI.]]',
                'hero_subheading' => 'Pay-Per-Click Advertising',
                'short_description' => 'Precision-targeted PPC campaigns across Google, Meta, YouTube & LinkedIn that put your brand in front of ready-to-buy customers — and turn clicks into revenue.',
                'content' => <<<'HTML'
<h2>Every Platform. Every Format. One Cohesive Strategy.</h2>
<p>We manage and optimize your paid campaigns across all major advertising networks to maximize visibility and conversions. Unlike agencies that “set and forget,” we actively optimize your campaigns daily to squeeze maximum value from every rupee spent.</p>
<p>From real-time bid optimization and negative keyword management to continuous A/B ad testing, custom conversion landing pages, and transparent live dashboards, our certified PPC specialists treat your budget like our own.</p>
HTML,
                'features' => [
                    ['title' => 'Google Search Ads', 'icon' => 'search', 'text' => 'Capture high-intent buyers actively searching for your products or services at the exact moment they’re ready to convert.'],
                    ['title' => 'Display & Banner Ads', 'icon' => 'layout', 'text' => 'Build massive brand awareness across millions of websites and apps in the Google Display Network with eye-catching visuals.'],
                    ['title' => 'Meta (Facebook & Instagram) Ads', 'icon' => 'share', 'text' => 'Reach hyper-specific demographics, interests, and lookalike audiences with thumb-stopping social ad creatives.'],
                    ['title' => 'Google Shopping & E-Commerce Ads', 'icon' => 'cart', 'text' => 'Showcase your product catalog directly in search results with rich product images, prices, and merchant ratings.'],
                    ['title' => 'YouTube Video Ads', 'icon' => 'video', 'text' => 'Engage audiences with skippable, non-skippable, and bumper video ads that tell your story and drive action.'],
                    ['title' => 'Remarketing & Retargeting', 'icon' => 'rocket', 'text' => 'Bring back 97% of visitors who left without buying using smart, personalized multi-channel retargeting funnels.'],
                ],
                'benefits' => [
                    ['title' => 'Real-Time Bid Optimization & Negative Keyword Pruning', 'icon' => 'bolt', 'text' => 'Daily bid adjustments and proactive negative keyword exclusions so you never waste budget on irrelevant clicks.'],
                    ['title' => 'Continuous A/B Ad Testing', 'icon' => 'sliders', 'text' => 'Systematic testing of headlines, visuals, offers, and CTAs to lower CPC and increase conversion rates.'],
                    ['title' => 'Custom Landing Page CRO', 'icon' => 'layout', 'text' => 'High-converting landing page feedback and optimization for higher Quality Scores and more leads per click.'],
                    ['title' => 'Transparent Live Dashboards', 'icon' => 'chart', 'text' => '100% visibility into your ad spend, clicks, conversions, Cost Per Lead (CPL), and Return on Ad Spend (ROAS).'],
                ],
                'process' => [
                    ['title' => 'Audit & Research', 'text' => 'Deep dive into your business, competitors, target audience, and existing ad accounts to uncover quick wins.'],
                    ['title' => 'Strategy & Setup', 'text' => 'Keyword research, audience segmentation, conversion tracking setup, ad copywriting, and landing page alignment.'],
                    ['title' => 'Launch & Test', 'text' => 'Go live with structured A/B tests across ad creatives, headlines, bidding strategies, and audience segments.'],
                    ['title' => 'Optimize & Scale', 'text' => 'Cut wasted spend with negative keywords, reallocate budget to winners, and scale profitable campaigns aggressively.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Google Ads', 'Meta Ads', 'YouTube Ads', 'LinkedIn Ads', 'Google Shopping', 'Microsoft / Bing Ads', 'Google Tag Manager', 'GA4']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Perfect for small businesses and startups taking their first steps into PPC',
                        'featured' => false,
                        'features' => implode("\n", [
                            '1 Platform (Google or Meta)',
                            'Up to ₹50,000 ad budget',
                            '3 ad campaigns setup',
                            'Weekly performance report',
                            'Dedicated account manager',
                            'Landing page feedback',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Growth',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'For growing businesses ready to scale their digital advertising aggressively',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            '3 Platforms (Google + Meta + 1)',
                            'Up to ₹3,00,000 ad budget',
                            '10 ad campaigns + A/B testing',
                            'Bi-weekly strategy calls',
                            'Conversion rate optimization',
                            'Competitor monitoring',
                            'Remarketing setup',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'Full-service PPC management for large brands with complex, multi-market campaigns',
                        'featured' => false,
                        'features' => implode("\n", [
                            'All platforms + custom mix',
                            'Unlimited ad budget',
                            'Unlimited campaigns',
                            'Weekly strategy calls',
                            'Dedicated 3-person team',
                            'Custom dashboard & reporting',
                            'Priority Google/Meta support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Find Out How Much Ad Spend [[You’re Wasting]]',
                'cta_description' => 'Get a comprehensive 30-point audit of your Google or Meta Ads account — completely free, no strings attached.',
            ],

            // 12. Branding (https://seashell-stork-114708.hostingersite.com/branding/)
            [
                'category_id' => $dmId,
                'slug' => 'branding',
                'name' => 'Branding & Identity',
                'menu_label' => 'Branding',
                'icon' => 'sparkles',
                'is_featured' => false,
                'h1' => 'We Craft Brands That [[Command Attention]]',
                'hero_subheading' => 'Full-Spectrum Brand Agency',
                'short_description' => 'From raw concept to iconic identity — we build cohesive, unforgettable brand experiences that turn audiences into loyal advocates and businesses into market leaders.',
                'content' => <<<'HTML'
<h2>Everything Your Brand Needs to Dominate</h2>
<p>A complete suite of branding solutions engineered to differentiate your business, connect emotionally with your audience, and drive long-term equity.</p>
<p>Every great brand is built on a foundation of strategy, creativity, and consistency. Our core branding pillars ensure your identity stands the test of time across digital, print, and physical touchpoints.</p>
HTML,
                'features' => [
                    ['title' => 'Brand Strategy & Positioning', 'icon' => 'compass', 'text' => 'Deep market research, competitor analysis, audience personas, and a rock-solid brand positioning framework that carves out your unique space in the market.'],
                    ['title' => 'Logo & Visual Identity', 'icon' => 'sparkles', 'text' => 'Memorable logos, custom typography, color psychology palettes, iconography, and visual systems designed to be timeless, versatile, and instantly recognizable.'],
                    ['title' => 'Brand Guidelines & Systems', 'icon' => 'file', 'text' => 'Comprehensive brand books and design rulebooks that ensure 100% visual and verbal consistency across every team member, partner, and touchpoint.'],
                    ['title' => 'Brand Voice & Messaging', 'icon' => 'message', 'text' => 'Compelling taglines, brand stories, elevator pitches, and tone-of-voice frameworks that articulate your value clearly and resonate with your ideal customers.'],
                    ['title' => 'Packaging & Print Collateral', 'icon' => 'layers', 'text' => 'Tactile unboxing experiences, product packaging, business stationery, brochures, and merchandise that bring your brand into the physical world with impact.'],
                    ['title' => 'Digital Branding & Rebranding', 'icon' => 'globe', 'text' => 'Social media kits, UI brand systems, motion graphics, and complete brand transformations for businesses ready to evolve and capture new markets.'],
                ],
                'benefits' => [
                    ['title' => 'Authenticity & Purpose', 'icon' => 'shield', 'text' => 'We uncover the genuine truth at the heart of your business so your brand resonates deeply with real people.'],
                    ['title' => 'Distinctive Visual Language', 'icon' => 'sparkles', 'text' => 'We reject cookie-cutter templates. Every line, color, and typeface is intentionally crafted so your brand stands out in a sea of sameness.'],
                    ['title' => 'Cross-Channel Consistency', 'icon' => 'layers', 'text' => 'Whether a customer sees you on Instagram, visits your website, or holds your product, the experience feels seamlessly unified.'],
                    ['title' => 'Future-Proof Scalability', 'icon' => 'rocket', 'text' => 'We design flexible brand systems that grow with your company — adapting effortlessly to new products, markets, and mediums.'],
                ],
                'process' => [
                    ['title' => 'Discovery & Research', 'text' => 'We immerse ourselves in your business, interview stakeholders, study your audience, and audit competitors to uncover strategic opportunities.'],
                    ['title' => 'Strategy & Concept', 'text' => 'We define your positioning, personality, messaging pillars, and moodboards — establishing the creative direction before any design begins.'],
                    ['title' => 'Design & Craft', 'text' => 'Our designers bring the strategy to life through logo concepts, color systems, typography, and real-world mockups refined through your feedback.'],
                    ['title' => 'Delivery & Launch', 'text' => 'We deliver production-ready asset libraries, comprehensive brand guidelines, and support your team through a successful brand rollout.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Adobe Illustrator', 'Adobe Photoshop', 'Adobe InDesign', 'Figma', 'Adobe After Effects', 'Pantone Color Systems']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Perfect for startups and small businesses just beginning their brand journey.',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Logo Design (3 Concepts)',
                            'Color Palette',
                            'Basic Brand Guide',
                            'Business Card Design',
                            'All File Formats Delivered',
                            'Social Media Kit',
                            'Brand Strategy Document',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Business',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'For growing businesses that need a complete, professional brand identity system.',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'Logo Design (5 Concepts)',
                            'Full Brand Color System',
                            'Comprehensive Brand Guide',
                            'Business Card + Stationery',
                            'Social Media Kit (10 templates)',
                            'Brand Strategy Document',
                            '2 Revision Rounds',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'Full-scale branding for established companies or major product/brand launches.',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Unlimited Logo Concepts',
                            'Full Brand Identity System',
                            '360° Brand Guidelines',
                            'All Print Collateral',
                            'Complete Social Media Kit',
                            'Brand Strategy + Positioning',
                            'Dedicated Brand Manager',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Build a Brand That [[Stands Above the Rest]]?',
                'cta_description' => 'Let’s turn your vision into a powerful brand identity that attracts dream clients and fuels business growth. Book your free 30-minute brand discovery call today.',
            ],

            // 13. Website Development (https://seashell-stork-114708.hostingersite.com/website-development/)
            [
                'category_id' => $devId,
                'slug' => 'website-development',
                'name' => 'Website Development',
                'menu_label' => 'Website Development',
                'icon' => 'globe',
                'is_featured' => true,
                'h1' => 'Professional [[Web Development]] Services',
                'hero_subheading' => 'Web Development Agency',
                'short_description' => 'We build fast, modern, and conversion-focused websites that grow your business, attract customers, and deliver real results.',
                'content' => <<<'HTML'
<h2>What We Build — Complete Web Development Solutions</h2>
<p>From simple landing pages to complex enterprise portals, we deliver end-to-end web development tailored to your business needs. A well-built website builds trust, drives sales, and establishes brand recognition.</p>
<p>Every website we build is engineered for lightning-fast performance, 100% mobile responsiveness, SEO-ready architecture, and enterprise security — backed by clean semantic code and ongoing technical support.</p>
HTML,
                'features' => [
                    ['title' => 'Custom Website Design', 'icon' => 'layout', 'text' => 'Pixel-perfect, brand-aligned UI/UX design crafted from scratch to captivate visitors and drive conversions.'],
                    ['title' => 'E-Commerce Development', 'icon' => 'cart', 'text' => 'Full-featured online stores on WooCommerce, Shopify, or custom platforms with seamless payment gateway integration.'],
                    ['title' => 'CMS & WordPress Sites', 'icon' => 'globe', 'text' => 'Easy-to-manage websites built on WordPress, Webflow, or custom CMS so you can update content effortlessly.'],
                    ['title' => 'Web App Development', 'icon' => 'code', 'text' => 'Scalable SaaS platforms, portals, dashboards, and custom web applications using React, Vue, Node.js, and Laravel.'],
                    ['title' => 'Landing Page Design', 'icon' => 'bolt', 'text' => 'High-converting landing pages optimized for speed, SEO, and paid ad campaigns to maximize your ROI.'],
                    ['title' => 'Maintenance & Support', 'icon' => 'shield', 'text' => 'Ongoing updates, security patches, performance monitoring, backups, and technical support to keep your site running 24/7.'],
                ],
                'benefits' => [
                    ['title' => 'Lightning-Fast Performance', 'icon' => 'bolt', 'text' => 'Optimized for speed with 95+ Google PageSpeed scores — fast sites rank higher and convert better.'],
                    ['title' => '100% Mobile Responsive', 'icon' => 'smartphone', 'text' => 'Flawless experience across all devices — mobile, tablet, and desktop — tested on every screen size.'],
                    ['title' => 'SEO-Ready Architecture', 'icon' => 'search', 'text' => 'Built with clean semantic code, schema markup, fast load times, and SEO best practices from day one.'],
                    ['title' => 'Secure & Reliable', 'icon' => 'shield', 'text' => 'SSL certificates, security hardening, regular backups, and GDPR-ready code included in every project.'],
                ],
                'process' => [
                    ['title' => 'Discovery & Planning', 'text' => 'We analyze your business goals, target audience, competitors, and define the project roadmap & sitemap.'],
                    ['title' => 'UI/UX Design', 'text' => 'Wireframes and interactive Figma prototypes designed for maximum engagement and brand consistency.'],
                    ['title' => 'Development & Coding', 'text' => 'Clean, scalable frontend and backend code built with modern frameworks, speed optimization, and SEO setup.'],
                    ['title' => 'Testing & Launch', 'text' => 'Rigorous QA testing across devices & browsers, smooth deployment, training, and ongoing post-launch support.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['HTML5', 'CSS3 / Tailwind', 'JavaScript', 'React.js', 'Next.js', 'Node.js', 'PHP / Laravel', 'WordPress', 'Shopify', 'WooCommerce', 'MySQL / MongoDB', 'Figma']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Best for small businesses & startups',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Up to 5 Pages',
                            'Mobile Responsive Design',
                            'Contact Form Integration',
                            'Basic SEO Setup',
                            'SSL Certificate',
                            'E-Commerce Functionality',
                            'Custom Animations',
                            'Priority Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Growth',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'Best for growing businesses',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'Up to 15 Pages',
                            'Premium Responsive Design',
                            'Advanced Contact Forms',
                            'Full SEO Optimization',
                            'SSL + Security Setup',
                            'Blog / CMS Integration',
                            'Custom Animations & Effects',
                            '3 Months Free Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'Best for large businesses & e-commerce',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Unlimited Pages',
                            'Custom Web Application',
                            'E-Commerce Integration',
                            'Advanced SEO + Analytics',
                            'Payment Gateway Setup',
                            'Admin Dashboard / Portal',
                            'Custom Integrations & APIs',
                            '6 Months Priority Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Build Your [[Dream Website]]?',
                'cta_description' => 'Let’s discuss your project and get a free consultation & quote within 24 hours.',
            ],

            // 14. Custom Web Development (https://seashell-stork-114708.hostingersite.com/custom-web-development/)
            [
                'category_id' => $devId,
                'slug' => 'custom-web-development',
                'name' => 'Custom Web Development',
                'menu_label' => 'Custom Web Apps',
                'icon' => 'code',
                'is_featured' => true,
                'h1' => 'We Build Custom Websites That [[Drive Business Growth]]',
                'hero_subheading' => 'Custom Web Development Services',
                'short_description' => 'From startup landing pages to enterprise web applications — we craft fast, secure, and scalable digital experiences tailored to your unique business goals.',
                'content' => <<<'HTML'
<h2>End-to-End Custom Web Solutions</h2>
<p>We don’t use cookie-cutter templates. Every line of code is written specifically for your brand, performance, and conversion goals. From custom web applications and e-commerce platforms to CMS solutions, PWAs, and RESTful/GraphQL APIs, our engineering team delivers software built to scale.</p>
<p>We combine technical excellence with business strategy to deliver websites that don’t just look great — they generate measurable ROI.</p>
HTML,
                'features' => [
                    ['title' => 'Bespoke Web Applications', 'icon' => 'code', 'text' => 'Full-stack custom web apps built with React, Next.js, Node.js, and Laravel — engineered for speed, scalability, and complex business logic.'],
                    ['title' => 'Custom E-Commerce Development', 'icon' => 'cart', 'text' => 'High-converting online stores with custom checkout flows, inventory management, payment gateway integrations, and multi-vendor capabilities.'],
                    ['title' => 'CMS & Enterprise Portals', 'icon' => 'layers', 'text' => 'Tailor-made content management systems, client dashboards, CRM/ERP portals, and headless CMS architectures that put you in full control.'],
                    ['title' => 'PWA & Single Page Apps (SPA)', 'icon' => 'bolt', 'text' => 'App-like web experiences with offline capabilities, push notifications, and instant page transitions using modern JavaScript frameworks.'],
                    ['title' => 'API Development & Integration', 'icon' => 'cpu', 'text' => 'Secure RESTful & GraphQL APIs connecting your website with third-party tools — CRMs, payment processors, ERPs, and cloud services.'],
                    ['title' => 'Maintenance, Security & Scaling', 'icon' => 'shield', 'text' => '24/7 monitoring, performance tuning, security audits, cloud migration, and continuous feature upgrades to keep your platform ahead.'],
                ],
                'benefits' => [
                    ['title' => '100% Tailored Architecture', 'icon' => 'sliders', 'text' => 'Built from the ground up around your workflow, brand identity, and user journey — zero bloated plugins or unnecessary code.'],
                    ['title' => 'Blazing Fast Core Web Vitals', 'icon' => 'bolt', 'text' => 'Optimized for 95+ Google PageSpeed scores, sub-2-second load times, and seamless responsiveness across all devices.'],
                    ['title' => 'SEO-First & Future-Ready', 'icon' => 'search', 'text' => 'Semantic HTML5, structured data schema, clean URL architecture, and modular code that scales effortlessly as your traffic grows.'],
                    ['title' => 'Dedicated Agile Team & Support', 'icon' => 'users', 'text' => 'Direct access to senior developers, weekly sprint demos, transparent project tracking, and post-launch warranty support.'],
                ],
                'process' => [
                    ['title' => 'Discovery & Strategy', 'text' => 'Deep dive into your business goals, target audience, competitor analysis, technical requirements, and project roadmap.'],
                    ['title' => 'UI/UX Wireframing', 'text' => 'Interactive Figma prototypes, user flow mapping, and custom visual design approved by you before coding begins.'],
                    ['title' => 'Agile Development', 'text' => 'Clean frontend & backend coding, database architecture, API integrations, and bi-weekly sprint demonstrations.'],
                    ['title' => 'QA, Launch & Scale', 'text' => 'Cross-browser testing, security auditing, speed optimization, zero-downtime deployment, and ongoing growth support.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['React.js', 'Next.js', 'TypeScript', 'Tailwind CSS', 'Vue.js', 'Node.js', 'Laravel / PHP', 'Python / Django', 'WordPress VIP', 'Shopify Plus', 'PostgreSQL', 'MongoDB', 'MySQL', 'AWS / Cloud', 'Docker']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Up to 5 Custom Pages',
                            'Responsive Design',
                            'Contact Form Integration',
                            'Basic SEO Setup',
                            'Social Media Links',
                            '1 Month Free Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Business',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'Up to 15 Custom Pages',
                            'Fully Custom UI/UX Design',
                            'CMS Integration',
                            'Advanced SEO Optimization',
                            'Speed & Performance Optimization',
                            '3 Months Free Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Unlimited Pages',
                            'Custom Web Application',
                            'API Development & Integration',
                            'Database Architecture',
                            'Priority Support 24/7',
                            '6 Months Free Maintenance',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Build Your [[Custom Website or Web App]]?',
                'cta_description' => 'Let’s discuss your idea. Get a free technical consultation, architecture blueprint, and detailed project quote within 24 hours.',
            ],

            // 15. Laravel Development (https://seashell-stork-114708.hostingersite.com/laravel-development/)
            [
                'category_id' => $devId,
                'slug' => 'laravel-development',
                'name' => 'Laravel Development',
                'menu_label' => 'Laravel Development',
                'icon' => 'layers',
                'is_featured' => false,
                'h1' => 'Build Powerful, Scalable Web Apps with [[Expert Laravel Development]]',
                'hero_subheading' => 'Top-Rated Laravel Development Company',
                'short_description' => 'From startup MVPs to enterprise-grade SaaS platforms, APIs, and eCommerce systems — our certified Laravel developers turn complex business logic into fast, secure, and future-ready applications.',
                'content' => <<<'HTML'
<h2>End-to-End Laravel Development Solutions</h2>
<p>We leverage the full power of the Laravel ecosystem to deliver custom, high-performance web applications tailored to your business goals.</p>
<p>Laravel is the world’s most popular PHP framework — and in the hands of our senior architects, it becomes your unfair competitive advantage with clean MVC architecture, built-in security, background queues, and high-speed scalability.</p>
HTML,
                'features' => [
                    ['title' => 'Custom Laravel Web Development', 'icon' => 'code', 'text' => 'Bespoke, feature-rich web applications engineered from scratch using clean MVC architecture, Eloquent ORM, and modular code that scales effortlessly.'],
                    ['title' => 'Laravel Enterprise & SaaS Solutions', 'icon' => 'layers', 'text' => 'Multi-tenant SaaS platforms, ERP/CRM systems, and enterprise portals with role-based access, subscription billing (Cashier), and cloud auto-scaling.'],
                    ['title' => 'RESTful & GraphQL API Development', 'icon' => 'cpu', 'text' => 'Secure, high-throughput APIs built with Laravel Sanctum & Passport to power mobile apps (iOS/Android), SPAs, and third-party integrations.'],
                    ['title' => 'Laravel eCommerce & Marketplace', 'icon' => 'cart', 'text' => 'High-converting online stores and multi-vendor marketplaces using Bagisto, Aimeos, or custom Laravel carts with seamless payment gateway integrations.'],
                    ['title' => 'Migration & Version Upgrades', 'icon' => 'rocket', 'text' => 'Zero-downtime migration from legacy PHP, CodeIgniter, or WordPress to the latest Laravel 11 — preserving your data, SEO, and business continuity.'],
                    ['title' => 'Maintenance, Audit & Support', 'icon' => 'shield', 'text' => '24/7 performance monitoring, code refactoring, security patching, bug fixes, and SLA-backed dedicated support to keep your app running at peak speed.'],
                ],
                'benefits' => [
                    ['title' => 'Fort-Knox Security', 'icon' => 'shield', 'text' => 'Built-in protection against SQL injection, XSS, CSRF attacks, encrypted data handling, and strict OWASP compliance out of the box.'],
                    ['title' => 'Blazing-Fast Performance', 'icon' => 'bolt', 'text' => 'Optimized with Laravel Octane, Redis caching, database indexing, and Horizon queue workers for sub-100ms response times.'],
                    ['title' => 'Clean, Test-Driven Code', 'icon' => 'code', 'text' => 'PSR-12 compliant code with automated PHPUnit & Pest testing, CI/CD pipelines, and comprehensive documentation for easy future scaling.'],
                ],
                'process' => [
                    ['title' => 'Discovery & Architecture', 'text' => 'We analyze your business requirements, define database schemas, plan API endpoints, and create a technical roadmap.'],
                    ['title' => 'UI/UX & Agile Sprint Coding', 'text' => 'Iterative development in 2-week sprints using clean MVC patterns, Blade/Vue/React frontends, and daily Git commits.'],
                    ['title' => 'QA, Security & Load Testing', 'text' => 'Automated unit/feature tests with Pest/PHPUnit, penetration testing, and stress testing to guarantee zero critical bugs.'],
                    ['title' => 'Cloud Deployment & Scale', 'text' => 'Zero-downtime CI/CD deployment to AWS/DigitalOcean/Forge with 24/7 monitoring and post-launch support.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Laravel 10 / 11', 'PHP 8.3+', 'Laravel Livewire', 'Laravel Nova / Filament', 'Laravel Octane & Horizon', 'Laravel Cashier & Sanctum', 'Vue.js / Inertia.js', 'React.js / Next.js', 'Tailwind CSS / Alpine.js', 'MySQL / PostgreSQL', 'Redis / ElasticSearch', 'AWS / Docker / Laravel Forge']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Laravel Web Application',
                            'Up to 5 Modules',
                            'MySQL Database',
                            'REST API Integration',
                            'Admin Panel',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Business',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'Custom Laravel Application',
                            'Up to 15 Modules',
                            'Multi-Database Support',
                            'Payment Gateway Integration',
                            'Advanced REST API & Webhooks',
                            'Role-Based Admin Panel',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Full-Stack Laravel Solution',
                            'Unlimited Modules',
                            'Microservices Architecture',
                            'GraphQL + REST APIs',
                            'Multi-Tenant SaaS Support',
                            'AWS / Cloud Deployment',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Build Your Next Big [[Laravel Application]]?',
                'cta_description' => 'Get a free 30-minute technical consultation, architecture review, and detailed project estimate within 24 hours.',
            ],

            // 16. WordPress Development (https://seashell-stork-114708.hostingersite.com/wordpress-development/)
            [
                'category_id' => $devId,
                'slug' => 'wordpress-development',
                'name' => 'WordPress Development',
                'menu_label' => 'WordPress Development',
                'icon' => 'layout',
                'is_featured' => false,
                'h1' => 'Powerful, Custom [[WordPress Websites]] That Drive Business Growth',
                'hero_subheading' => 'Expert WordPress Development Company',
                'short_description' => 'From lightning-fast corporate websites and custom themes to high-converting WooCommerce stores — we engineer secure, SEO-ready, and scalable WordPress solutions tailored to your brand.',
                'content' => <<<'HTML'
<h2>End-to-End WordPress Development Solutions</h2>
<p>Everything you need to build, launch, scale, and maintain a world-class WordPress presence under one roof. We don’t just install pre-made templates — we engineer high-performance digital assets with clean code, enterprise-grade security, and conversion-focused UX.</p>
HTML,
                'features' => [
                    ['title' => 'Custom WordPress Website Design', 'icon' => 'layout', 'text' => 'Bespoke, brand-aligned WordPress websites designed from scratch with pixel-perfect responsiveness, intuitive UX, and clean semantic code.'],
                    ['title' => 'WooCommerce Store Development', 'icon' => 'cart', 'text' => 'High-converting online stores with custom product pages, seamless checkout flows, payment gateway integrations, and inventory automation.'],
                    ['title' => 'Custom Theme & Gutenberg Blocks', 'icon' => 'sliders', 'text' => 'Lightweight, bloat-free custom themes and reusable Gutenberg / Elementor blocks that give your team effortless drag-and-drop editing power.'],
                    ['title' => 'Custom Plugin & API Integration', 'icon' => 'code', 'text' => 'Tailor-made WordPress plugins and seamless third-party integrations with CRMs (HubSpot, Salesforce), ERPs, payment gateways, and REST APIs.'],
                    ['title' => 'Speed Optimization & Migration', 'icon' => 'bolt', 'text' => 'Core Web Vitals optimization for 90+ PageSpeed scores, and zero-downtime migration from Wix, Shopify, Squarespace, or legacy CMS to WordPress.'],
                    ['title' => 'Security, Maintenance & Support', 'icon' => 'shield', 'text' => '24/7 uptime monitoring, malware protection, daily off-site backups, core/plugin updates, and dedicated technical support plans.'],
                ],
                'benefits' => [
                    ['title' => '90+ Google PageSpeed Score', 'icon' => 'bolt', 'text' => 'No bloated themes or unnecessary plugins — just blazing-fast Core Web Vitals and sub-2-second load times.'],
                    ['title' => '100% Technical SEO Ready', 'icon' => 'search', 'text' => 'Built with schema markup, clean URL hierarchy, optimized heading tags, XML sitemaps, and full RankMath / Yoast SEO configuration.'],
                    ['title' => 'Hardened Enterprise Security', 'icon' => 'shield', 'text' => 'Multi-layer firewall protection, SSL encryption, brute-force prevention, database hardening, and automated daily cloud backups.'],
                    ['title' => 'Easy for Your Team to Manage', 'icon' => 'users', 'text' => 'Intuitive custom dashboards with ACF / Gutenberg so non-technical team members can update text, images, and pages in seconds — plus free training.'],
                ],
                'process' => [
                    ['title' => 'Discovery & Strategy', 'text' => 'We analyze your business goals, target audience, competitors, and define the sitemap, tech stack, and project roadmap.'],
                    ['title' => 'UI/UX & Wireframing', 'text' => 'We create interactive Figma prototypes and custom visual designs tailored to your brand identity for your approval before coding.'],
                    ['title' => 'Custom WP Development', 'text' => 'Our developers build your custom theme, plugins, WooCommerce store, and integrations on a staging server with clean, scalable code.'],
                    ['title' => 'QA, Launch & Support', 'text' => 'Rigorous speed, security, SEO, and cross-device testing — followed by zero-downtime launch, team training, and 30 days free support.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['WordPress 6.x', 'WooCommerce', 'PHP 8.x & MySQL', 'Gutenberg & Full Site Editing', 'Elementor Pro', 'Advanced Custom Fields (ACF)', 'WPGraphQL & Headless WP', 'React / Next.js', 'Yoast / RankMath SEO', 'WP Rocket & Redis Cache', 'Stripe / Razorpay / PayPal', 'AWS / Cloudways / WP Engine']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Best for startups & small businesses',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Custom WordPress Website',
                            'Up to 5 Web Pages',
                            'Mobile Responsive Design',
                            'Basic SEO Setup',
                            'Contact Form Integration',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Business',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'Perfect for growing businesses',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'Custom WordPress Website',
                            'Up to 15 Web Pages',
                            'WooCommerce Integration',
                            'Advanced SEO + Schema',
                            'Payment Gateway Setup',
                            'Custom Plugin Development',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'For large-scale WordPress solutions',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Fully Custom WordPress Build',
                            'Unlimited Pages',
                            'Multi-Vendor WooCommerce',
                            'Full SEO + Performance Audit',
                            'Custom Theme + Plugins',
                            'AWS / Cloud Hosting Setup',
                            'Priority 24/7 Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Build a High-Converting [[WordPress Website]]?',
                'cta_description' => 'Let’s discuss your project requirements. Get a free consultation, technical roadmap, and transparent quote within 24 hours.',
            ],

            // 17. React.js Development (https://seashell-stork-114708.hostingersite.com/react-js-development/)
            [
                'category_id' => $devId,
                'slug' => 'react-js-development',
                'name' => 'React.js Development',
                'menu_label' => 'React.js Development',
                'icon' => 'cpu',
                'is_featured' => false,
                'h1' => 'Build Fast, Scalable & Modern Web Apps with [[React.js Development]]',
                'hero_subheading' => 'React.js Development Company',
                'short_description' => 'We engineer high-performance Single Page Applications, SSR Next.js platforms, and enterprise-grade React solutions that deliver blazing speed, seamless UX, and measurable business growth.',
                'content' => <<<'HTML'
<h2>End-to-End React.js Development Services</h2>
<p>From interactive user interfaces to complex enterprise ecosystems, our React engineers build modular, future-ready applications tailored to your business goals.</p>
<p>Backed by Meta and trusted by Netflix, Airbnb, and Uber — React.js is the gold standard for modern frontend engineering, offering Virtual DOM speed, reusable component architecture, and SEO-friendly SSR with Next.js.</p>
HTML,
                'features' => [
                    ['title' => 'Custom React.js Web Applications', 'icon' => 'code', 'text' => 'Tailor-made, scalable React applications architected from scratch with clean component hierarchies, TypeScript safety, and modern hooks.'],
                    ['title' => 'Single Page Applications (SPA)', 'icon' => 'bolt', 'text' => 'Lightning-fast, dynamic SPAs with zero page reloads, smooth transitions, and app-like responsiveness that keep users engaged.'],
                    ['title' => 'Next.js & SSR Development', 'icon' => 'globe', 'text' => 'SEO-friendly Server-Side Rendered (SSR) and Static Site Generated (SSG) applications using Next.js for instant load times and top search rankings.'],
                    ['title' => 'Enterprise UI/UX & Design Systems', 'icon' => 'layers', 'text' => 'Reusable component libraries and pixel-perfect design systems built with Tailwind CSS, Material UI, Shadcn, and Storybook.'],
                    ['title' => 'Legacy to React Migration', 'icon' => 'rocket', 'text' => 'Seamless migration from jQuery, Angular, PHP, or older React class components to modern React 18+ without data loss or downtime.'],
                    ['title' => 'API Integration & Maintenance', 'icon' => 'cpu', 'text' => 'Robust RESTful & GraphQL API integrations, performance auditing, code refactoring, and 24/7 SLA-backed technical support.'],
                ],
                'benefits' => [
                    ['title' => 'Blazing-Fast Virtual DOM', 'icon' => 'bolt', 'text' => 'React’s Virtual DOM and concurrent rendering update only what changes — delivering sub-second load times and 95+ Core Web Vitals scores.'],
                    ['title' => 'Reusable Component Architecture', 'icon' => 'layers', 'text' => 'Modular UI components cut development time by up to 40%, ensure brand consistency, and make future feature rollouts effortless.'],
                    ['title' => 'SEO-Friendly with SSR', 'icon' => 'search', 'text' => 'Paired with Next.js Server-Side Rendering, your React web app gets crawled and indexed by Google just like a static HTML site.'],
                    ['title' => 'Unmatched Scalability & Security', 'icon' => 'shield', 'text' => 'Whether you have 1,000 or 10 million users, React’s unidirectional data flow and enterprise state management keep your app stable and secure.'],
                ],
                'process' => [
                    ['title' => 'Discovery & Architecture', 'text' => 'We analyze requirements, define component trees, state architecture, API contracts, and tech stack specifications.'],
                    ['title' => 'UI/UX & Prototyping', 'text' => 'Interactive Figma wireframes and design systems translated into responsive, accessible React components.'],
                    ['title' => 'Agile React Development', 'text' => 'Clean, modular TypeScript/React code written in 2-week sprints with continuous integration and demo previews.'],
                    ['title' => 'QA, Launch & Scale', 'text' => 'Automated testing (Jest/Cypress), Lighthouse optimization, cloud deployment, and ongoing post-launch support.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['React 18+', 'Next.js', 'TypeScript', 'JavaScript ES6+', 'Vite', 'Redux Toolkit', 'Zustand', 'React Query', 'GraphQL / Apollo', 'REST APIs', 'Tailwind CSS', 'Material UI', 'Shadcn UI', 'Framer Motion', 'Storybook', 'Node.js', 'Jest / RTL', 'Cypress', 'Docker / AWS', 'Vercel / CI-CD']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Perfect for startups & small apps',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Up to 5 React Components',
                            'REST API Integration',
                            'Redux State Management',
                            'Responsive UI Design',
                            'Code on GitHub',
                            'Vercel/Netlify Deployment',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Business',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'For growing businesses & SaaS apps',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'GraphQL / REST API',
                            'Next.js SSR/SSG Support',
                            'TypeScript Codebase',
                            '3 Months Free Support',
                            'Unit & Integration Testing',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'For large-scale enterprise applications',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Full-Stack React + Node.js',
                            'Micro-Frontend Architecture',
                            'AWS / Azure Deployment',
                            'Performance Optimization',
                            '6 Months Free Support',
                            'Custom SLA Agreement',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Build a High-Performance [[React.js Application]]?',
                'cta_description' => 'Let’s discuss your project idea. Get a free technical consultation, architecture blueprint, and cost estimate within 24 hours.',
            ],

            // 18. Shopify Development (https://seashell-stork-114708.hostingersite.com/shopify-development/)
            [
                'category_id' => $devId,
                'slug' => 'shopify-development',
                'name' => 'Shopify Development',
                'menu_label' => 'Shopify Development',
                'icon' => 'cart',
                'is_featured' => true,
                'h1' => 'Build a High-Converting [[Shopify Store]] That Scales Your Brand',
                'hero_subheading' => 'Certified Shopify & Shopify Plus Experts',
                'short_description' => 'From bespoke Shopify theme design and custom app development to seamless migrations and Shopify Plus enterprise solutions — we craft fast, conversion-obsessed eCommerce experiences.',
                'content' => <<<'HTML'
<h2>End-to-End Shopify Development Services</h2>
<p>Everything you need to launch, optimize, and scale a profitable online store on Shopify and Shopify Plus. We combine deep Shopify technical expertise with conversion-focused design to deliver online stores that generate measurable revenue from day one.</p>
HTML,
                'features' => [
                    ['title' => 'Custom Shopify Store Setup & Design', 'icon' => 'cart', 'text' => 'Complete store architecture, brand-aligned UI/UX, product catalog setup, payment gateways, shipping zones, and tax configuration ready for launch.'],
                    ['title' => 'Shopify Theme Development & Customization', 'icon' => 'code', 'text' => 'Pixel-perfect, mobile-first custom Shopify 2.0 themes built with clean Liquid code, or deep customization of premium themes for maximum speed.'],
                    ['title' => 'Shopify Plus & Enterprise Solutions', 'icon' => 'layers', 'text' => 'High-volume commerce architecture, custom checkout extensibility, B2B wholesale portals, Shopify Flow automation, and multi-currency international stores.'],
                    ['title' => 'Seamless Store Migration to Shopify', 'icon' => 'rocket', 'text' => 'Zero-downtime migration from WooCommerce, Magento, BigCommerce, or Wix — preserving all products, customers, order history, and SEO rankings (301 redirects).'],
                    ['title' => 'Custom Shopify App & API Integrations', 'icon' => 'sliders', 'text' => 'Private/public Shopify apps and seamless integrations with ERPs, CRMs, 3PL logistics, Klaviyo, payment gateways, and subscription engines.'],
                    ['title' => 'Speed Optimization, SEO & Ongoing Support', 'icon' => 'bolt', 'text' => 'Core Web Vitals optimization, conversion rate optimization (CRO), technical eCommerce SEO, and dedicated monthly maintenance retainers.'],
                ],
                'benefits' => [
                    ['title' => 'Conversion-First Approach', 'icon' => 'chart', 'text' => 'We don’t just make pretty stores — every product page, cart drawer, and checkout step is engineered to maximize AOV and conversion rate.'],
                    ['title' => '90+ PageSpeed Performance', 'icon' => 'bolt', 'text' => 'Bloated apps kill sales. We write clean, lightweight Liquid and JavaScript code to ensure sub-2-second load times on mobile and desktop.'],
                    ['title' => 'Built-In eCommerce SEO', 'icon' => 'search', 'text' => 'Structured product schema, clean URL architecture, meta optimization, and sitemap setup so your products rank higher on Google organically.'],
                    ['title' => 'Dedicated Post-Launch Support', 'icon' => 'shield', 'text' => 'Every project includes hands-on training for your team plus 30 days of free post-launch technical support and bug fixes.'],
                ],
                'process' => [
                    ['title' => 'Discovery & Strategy', 'text' => 'We audit your brand, products, competitors, and target audience to create a tailored Shopify architecture and conversion roadmap.'],
                    ['title' => 'UI/UX & Theme Design', 'text' => 'Our designers craft high-converting Figma wireframes and mobile-first layouts focused on user journey and seamless checkout.'],
                    ['title' => 'Development & Integrations', 'text' => 'We code your Shopify 2.0 theme, configure products, integrate payment/shipping APIs, and set up essential marketing apps.'],
                    ['title' => 'QA, Launch & Growth', 'text' => 'Rigorous speed, mobile, and checkout testing followed by a smooth go-live, team training, and ongoing CRO support.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Shopify OS 2.0', 'Shopify Plus', 'Liquid Template Engine', 'Hydrogen & Oxygen (Headless)', 'Storefront & GraphQL APIs', 'Klaviyo & Marketing Automation', 'Razorpay / Stripe / PayPal', 'Shiprocket / DHL / FedEx 3PL', 'Recharge Subscriptions', 'ERP & CRM Sync (Zoho / HubSpot)', 'Judge.me / Loox Reviews', 'GA4 & Meta Pixel Tracking']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Basic Shopify Store Setup',
                            'Free Theme Customization',
                            'Up to 50 Products',
                            'Payment Gateway Integration',
                            'Mobile Responsive Design',
                            '30 Days Free Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Business',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'Custom Shopify Theme',
                            'Up to 500 Products',
                            'Custom App Integration',
                            'Multi-Currency Support',
                            'SEO Optimization',
                            'Advanced Analytics Setup',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Shopify Plus Development',
                            'Unlimited Products',
                            'Custom Checkout Flow',
                            'ERP / CRM Integration',
                            'Multi-Store Management',
                            '90 Days Dedicated Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Build or Scale Your [[Shopify Store]]?',
                'cta_description' => 'Get a free 30-minute consultation and store audit with our senior Shopify architects. No obligation, just actionable insights.',
            ],

            // 19. App Development (https://seashell-stork-114708.hostingersite.com/app-development/)
            [
                'category_id' => $devId,
                'slug' => 'app-development',
                'name' => 'Mobile App Development',
                'menu_label' => 'App Development',
                'icon' => 'smartphone',
                'is_featured' => true,
                'h1' => 'Turn Your App Idea Into a [[High-Performing Mobile Reality]]',
                'hero_subheading' => 'Top-Rated Mobile App Development Company',
                'short_description' => 'We design, build, and scale custom iOS, Android, and cross-platform mobile applications that delight users, streamline operations, and accelerate your business growth.',
                'content' => <<<'HTML'
<h2>End-to-End Mobile App Development Solutions</h2>
<p>From startup MVPs to enterprise-grade ecosystems — we cover every stage of the mobile application lifecycle under one roof. We combine cutting-edge mobile engineering with business strategy to deliver apps that don’t just look great — they perform, convert, and scale.</p>
HTML,
                'features' => [
                    ['title' => 'iOS App Development', 'icon' => 'smartphone', 'text' => 'Native, high-performance iPhone, iPad, and Apple Watch applications built with Swift & SwiftUI, fully compliant with Apple App Store guidelines.'],
                    ['title' => 'Android App Development', 'icon' => 'code', 'text' => 'Scalable, secure, and feature-rich native Android apps crafted using Kotlin & Jetpack Compose to run seamlessly across thousands of Android devices.'],
                    ['title' => 'Cross-Platform Apps (Flutter & React Native)', 'icon' => 'layers', 'text' => 'Launch on both iOS and Android simultaneously with a single codebase — reducing development cost by up to 40% without compromising native feel.'],
                    ['title' => 'App UI/UX Design & Prototyping', 'icon' => 'sparkles', 'text' => 'User-centric wireframes, interactive Figma prototypes, and pixel-perfect interfaces engineered for high engagement and retention.'],
                    ['title' => 'Enterprise & AI-Powered Apps', 'icon' => 'cpu', 'text' => 'Custom ERP/CRM mobile portals, IoT integration, AI chatbots, machine learning features, and cloud-native backend APIs built for scale.'],
                    ['title' => 'App Store Launch, ASO & Maintenance', 'icon' => 'rocket', 'text' => 'Hassle-free App Store & Play Store publishing, App Store Optimization (ASO), 24/7 monitoring, OS version upgrades, and ongoing support.'],
                ],
                'benefits' => [
                    ['title' => 'Agile & Transparent Delivery', 'icon' => 'rocket', 'text' => '2-week sprint cycles with clickable builds, weekly demos, and full visibility via Jira, Slack, or WhatsApp.'],
                    ['title' => 'Enterprise-Grade Security', 'icon' => 'shield', 'text' => 'End-to-end encryption, secure API authentication (OAuth2/JWT), GDPR/HIPAA compliance, and rigorous penetration testing.'],
                    ['title' => '100% Source Code Ownership', 'icon' => 'code', 'text' => 'You own the full intellectual property, Git repositories, design assets, and cloud infrastructure from day one — zero vendor lock-in.'],
                    ['title' => 'Dedicated Post-Launch Support', 'icon' => 'users', 'text' => 'Free bug-fix warranty after launch, proactive OS compatibility updates, server scaling, and feature roadmap support.'],
                ],
                'process' => [
                    ['title' => 'Discovery & Strategy', 'text' => 'We analyze your business goals, target audience, competitors, and define the feature roadmap, tech stack, and project blueprint.'],
                    ['title' => 'UI/UX Wireframing', 'text' => 'Our designers craft user journeys, low-fidelity wireframes, and interactive Figma prototypes so you can experience your app before coding begins.'],
                    ['title' => 'Agile Development & QA', 'text' => 'Frontend mobile coding, backend API engineering, third-party integrations, and automated + manual testing across real iOS & Android devices.'],
                    ['title' => 'Store Launch & Growth', 'text' => 'Complete App Store & Google Play deployment, ASO setup, analytics tracking, and continuous post-launch maintenance & scaling.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Swift', 'SwiftUI', 'Kotlin', 'Java', 'Flutter', 'React Native', 'Node.js', 'Python / Django', 'Laravel', 'Firebase', 'GraphQL / REST', 'PostgreSQL', 'MongoDB', 'AWS', 'Google Cloud', 'Docker', 'Figma', 'Adobe XD', 'Lottie', 'Design Systems']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Single Platform (Android or iOS)',
                            'Up to 8 App Screens',
                            'Basic UI/UX Design',
                            'User Authentication',
                            'Push Notifications',
                            '30 Days Free Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Business',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'iOS + Android (Cross-Platform)',
                            'Up to 20 App Screens',
                            'Custom UI/UX Design',
                            'Payment Gateway Integration',
                            'Social Login & OTP Auth',
                            'Real-Time Chat / Notifications',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Native iOS + Android Apps',
                            'Unlimited Screens',
                            'AI / ML Integration',
                            'Advanced Analytics',
                            'Multi-Language & Localization',
                            'Dedicated Project Manager',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Affordable App Development Plans [[For Every Business]]',
                'cta_description' => 'Transparent, flexible pricing tailored to your app’s complexity and business goals. No hidden charges — ever. Need a custom quote? Contact us — we tailor every project to your unique requirements.',
            ],

            // 20. UI/UX Design (https://seashell-stork-114708.hostingersite.com/ui-ux/)
            [
                'category_id' => $devId,
                'slug' => 'ui-ux',
                'name' => 'UI/UX Design',
                'menu_label' => 'UI/UX Design',
                'icon' => 'sliders',
                'is_featured' => false,
                'h1' => 'Design Digital Experiences Users [[Fall in Love With]]',
                'hero_subheading' => 'Human-Centered UI/UX Design Agency',
                'short_description' => 'We blend deep user research, intuitive information architecture, and pixel-perfect visual craft to build web & mobile interfaces that boost engagement, retention, and conversions.',
                'content' => <<<'HTML'
<h2>End-to-End UI/UX Design Services</h2>
<p>From early-stage discovery to developer-ready design systems, we cover every touchpoint of your product’s user journey. Great design isn’t just about looking pretty — it’s about solving real business problems and creating measurable ROI.</p>
HTML,
                'features' => [
                    ['title' => 'UX Research & Strategy', 'icon' => 'search', 'text' => 'User interviews, persona development, competitor benchmarking, and journey mapping to uncover what truly motivates your audience.'],
                    ['title' => 'Wireframing & Interactive Prototyping', 'icon' => 'layout', 'text' => 'Low- to high-fidelity wireframes and clickable Figma prototypes that let you test flows and validate ideas before writing a single line of code.'],
                    ['title' => 'Web & SaaS UI Design', 'icon' => 'globe', 'text' => 'Clean, modern, conversion-focused interfaces for websites, complex dashboards, B2B SaaS platforms, and enterprise web applications.'],
                    ['title' => 'Mobile App UI/UX (iOS & Android)', 'icon' => 'smartphone', 'text' => 'Thumb-friendly, native-feeling mobile experiences following Apple Human Interface and Google Material Design 3 guidelines.'],
                    ['title' => 'Design Systems & Component Libraries', 'icon' => 'layers', 'text' => 'Scalable UI kits, design tokens, style guides, and reusable Figma component libraries that keep your product consistent as you scale.'],
                    ['title' => 'UX Audit & Product Redesign', 'icon' => 'chart', 'text' => 'Heuristic evaluations, heatmap analysis, usability testing, and friction-point fixes to transform underperforming products into high-converting ones.'],
                ],
                'benefits' => [
                    ['title' => 'Conversion-First Approach', 'icon' => 'chart', 'text' => 'Every button, micro-interaction, and visual hierarchy is intentionally crafted to reduce drop-offs and turn visitors into paying customers.'],
                    ['title' => 'WCAG 2.1 Accessible & Inclusive', 'icon' => 'shield', 'text' => 'We design with proper contrast ratios, keyboard navigation, and screen-reader friendliness so your product works seamlessly for every user.'],
                    ['title' => 'Seamless Developer Handoff', 'icon' => 'code', 'text' => 'Auto-layout Figma files, organized variables, CSS specs, and asset exports so your engineering team builds 2x faster with zero guesswork.'],
                    ['title' => 'Dedicated Senior Designers', 'icon' => 'users', 'text' => 'Work directly with experienced product designers — daily Slack/Figma updates, transparent timelines, and iterative feedback loops.'],
                ],
                'process' => [
                    ['title' => 'Discover & Empathize', 'text' => 'Stakeholder workshops, user research, competitor audits, and defining core KPIs and user personas.'],
                    ['title' => 'Structure & Wireframe', 'text' => 'Information architecture, user flow diagrams, and low-fidelity wireframes to nail the layout and logic.'],
                    ['title' => 'Visual UI & Prototype', 'text' => 'High-fidelity visual design, micro-interactions, design system tokens, and clickable prototypes in Figma.'],
                    ['title' => 'Test & Handoff', 'text' => 'Usability testing with real users, iterative refinements, and complete developer handoff with QA support.'],
                ],
                'technologies' => array_map(fn ($t) => ['title' => $t], ['Figma', 'Adobe XD', 'Sketch', 'Framer', 'Illustrator', 'Photoshop', 'ProtoPie', 'InVision', 'Maze Testing', 'Hotjar / Clarity', 'Miro / FigJam', 'Storybook']),
                'pricing' => [
                    [
                        'title' => 'Starter',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Up to 5 Screen Designs',
                            'Wireframing Included',
                            '1 Revision Round',
                            'Figma Source Files',
                            'Basic UX Audit',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Business',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'Up to 15 Screen Designs',
                            'Full UX Research',
                            'Interactive Prototype',
                            '3 Revision Rounds',
                            'Design System Included',
                            'Developer Handoff Files',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => '',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Unlimited Screens',
                            'Full UX Strategy',
                            'Usability Testing',
                            'Unlimited Revisions',
                            'Dedicated Design Lead',
                            'Post-Launch Support',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
                'cta_heading' => 'Ready to Transform Your Product’s [[User Experience]]?',
                'cta_description' => 'Book a free 30-minute UX consultation & mini heuristic audit with our lead product designer. No commitment required.',
            ],
        ];
    }
}
