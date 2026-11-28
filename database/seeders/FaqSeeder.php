<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $generalCat = FaqCategory::updateOrCreate(['slug' => 'general'], ['name' => 'General', 'sort_order' => 0]);

        // 1. Homepage / General FAQs (7 authentic FAQs from the old homepage `home` ID 9)
        $generalFaqs = [
            [
                'What makes Tech Alphonic an AI-driven digital marketing company?',
                '<p>Tech Alphonic integrates artificial intelligence into digital marketing strategies to analyze customer behavior, automate campaigns, predict trends, and optimize performance across channels such as SEO, social media, and paid advertising.</p>',
            ],
            [
                'Why should I hire a digital marketing agency instead of managing marketing in-house?',
                '<p>Hiring a digital marketing agency gives your business access to experienced professionals, advanced marketing tools, and data-driven strategies that help generate better results while saving time and operational costs.</p>',
            ],
            [
                'How does AI improve digital marketing performance?',
                '<p>Artificial intelligence improves digital marketing by analyzing large amounts of data, identifying customer behavior patterns, optimizing ad campaigns in real time, and helping businesses target the right audience more effectively.</p>',
            ],
            [
                'What are the services your digital marketing company provides?',
                '<p>Tech Alphonic provides a complete range of digital marketing and technology services including Search Engine Optimization (SEO), AI SEO, Social Media Optimization (SMO), Pay-Per-Click (PPC) Advertising, Content Marketing, Website Development, and Mobile App Development.</p>',
            ],
            [
                'Can AI-driven marketing help small businesses?',
                '<p>Yes. AI-driven digital marketing helps small businesses compete effectively by automating repetitive tasks, targeting high-intent audiences, and maximizing return on marketing budgets.</p>',
            ],
            [
                'How long does it take to see results from digital marketing?',
                '<p>Paid advertising (PPC) and social media campaigns can begin generating leads within days, while organic strategies like SEO typically show measurable improvements in 3 to 6 months with compounding long-term growth.</p>',
            ],
            [
                'What is the reason Tech Alphonic is viewed as a reliable digital marketing partner in India?',
                '<p>Tech Alphonic combines 10+ years of industry experience, AI-powered marketing tools, customized growth strategies, and transparent reporting to deliver measurable traffic, qualified leads, and higher conversions for clients across India and globally.</p>',
            ],
        ];

        $generalIds = [];
        foreach ($generalFaqs as $i => [$q, $a]) {
            $existing = ($i < 6 ? Faq::find($i + 1) : null) ?? Faq::where('question', $q)->first();
            if ($existing) {
                $existing->update([
                    'faq_category_id' => $generalCat->id,
                    'question' => $q,
                    'answer' => $a,
                    'is_active' => true,
                    'sort_order' => $i,
                ]);
                $generalIds[] = $existing->id;
            } else {
                $created = Faq::create([
                    'faq_category_id' => $generalCat->id,
                    'question' => $q,
                    'answer' => $a,
                    'is_active' => true,
                    'sort_order' => $i,
                ]);
                $generalIds[] = $created->id;
            }
        }

        // 2. Contact Us Page FAQs (7 authentic FAQs verbatim from https://seashell-stork-114708.hostingersite.com/contact/)
        $contactFaqs = [
            ['What services does Techalphonic Pvt Ltd provide?', '<p>Techalphonic Pvt Ltd offers website development, mobile app development, digital marketing, SEO services, social media optimization (SMO), graphic design, and branding solutions to help businesses build and grow their online presence.</p>'],
            ['Do you create custom websites?', '<p>Yes, we design and develop fully customized, responsive, and SEO-friendly websites tailored to your business needs. Our websites are optimized for speed, user experience, and mobile devices.</p>'],
            ['Which technologies do you use for web development?', '<p>We work with modern web technologies including WordPress, Shopify, HTML, CSS, JavaScript, PHP, Laravel, ReactJS, and other frameworks depending on project requirements.</p>'],
            ['Do you provide mobile app development services?', '<p>Yes, we develop user-friendly and scalable mobile applications for Android and iOS platforms to help businesses connect better with their customers.</p>'],
            ['What is included in your digital marketing services?', '<p>Our digital marketing services include Search Engine Optimization (SEO), Social Media Marketing (SMM), Social Media Optimization (SMO), content marketing, online branding, and paid advertising campaigns.</p>'],
            ['How can SEO help my business?', '<p>SEO helps improve your website’s visibility on search engines like Google, attract organic traffic, generate quality leads, and increase online sales.</p>'],
            ['Do you provide local SEO services?', '<p>Yes, we offer local SEO services including Google Business Profile optimization, local keyword targeting, and directory listings to help businesses attract nearby customers.</p>'],
        ];
        $contactIds = $this->upsertFaqGroup('contact', $contactFaqs, 100);
        if ($contactPage = Page::where('slug', 'contact')->first()) {
            $contactPage->syncFaqs($contactIds);
        }

        // 3. Packages Page FAQs (6 authentic FAQs verbatim from https://seashell-stork-114708.hostingersite.com/packages/)
        $packageFaqs = [
            ['Can I switch my plan after signing up?', '<p>Yes, absolutely! You can upgrade or downgrade your plan at any time. Changes take effect from the next billing cycle. Our team will ensure a smooth transition with no data loss.</p>'],
            ['How long before I see results from SEO?', '<p>SEO typically shows measurable improvements in 3-4 months. However, you may start seeing initial ranking movements and traffic improvements in the first 4-6 weeks. We track progress and share weekly reports so you’re always informed.</p>'],
            ['Is there any contract or long-term commitment?', '<p>No long-term contracts! All our monthly packages are month-to-month. You can cancel with a 30-day notice. We earn your business every single month through results and service quality.</p>'],
            ['What does the ad spend in PPC packages mean?', '<p>The ad spend limit mentioned (e.g., ₹25,000 for Starter) is the maximum Google/Meta Ads budget we will manage for you, paid directly to the ad platform. Our management fee is separate and included in the package price. You always have full visibility of how every rupee is spent.</p>'],
            ['Do you offer custom packages for specific requirements?', '<p>Yes! Every business is unique. Contact us for a free consultation and we’ll create a custom package tailored to your goals, industry, and budget. No cookie-cutter solutions — only what you actually need.</p>'],
            ['What payment methods do you accept?', '<p>We accept UPI, Bank Transfer (NEFT/RTGS/IMPS), Credit/Debit Cards, and Razorpay. For international clients, we accept PayPal and wire transfers. GST invoices are provided for all payments.</p>'],
        ];
        $packageIds = $this->upsertFaqGroup('packages', $packageFaqs, 120);
        if ($packagesPage = Page::where('slug', 'packages')->first()) {
            $packagesPage->syncFaqs($packageIds);
        }

        // 3b. SEO by Industry Page FAQs (6 authentic FAQs verbatim from https://seashell-stork-114708.hostingersite.com/seo-by-industry/)
        $industrySeoFaqs = [
            ['How is Industry SEO different from regular SEO?', '<p>Regular SEO often uses a one-size-fits-all template. Industry SEO is tailored to your vertical’s exact search intent, customer buying cycle, local vs. national dynamics, compliance rules (like medical or financial guidelines), and industry-specific directories and schema markup.</p>'],
            ['How long does it take to see results in my industry?', '<p>Most businesses begin seeing measurable keyword movement and traffic growth within 60–90 days, with strong page-1 rankings and consistent lead generation achieved within 4–6 months depending on your industry’s competition level.</p>'],
            ['Do you work with industries not listed on this page?', '<p>Yes! While we’ve highlighted our top verticals above, we have ranked businesses across 30+ industries including Legal, Dental, SaaS, Manufacturing, Finance, Interior Design, Automotive, Fitness, and more. Contact us for a custom industry strategy.</p>'],
            ['Can you guarantee #1 ranking on Google for my industry keywords?', '<p>No ethical SEO agency can guarantee a #1 slot because Google controls the algorithm. However, our proven industry playbooks consistently rank 90%+ of our clients on Page 1 for high-intent commercial keywords that drive real business revenue.</p>'],
            ['Will I get an SEO manager who understands my industry?', '<p>Absolutely. You’ll be paired with a dedicated SEO account manager who has prior experience in your vertical and understands your target audience, terminology, and business model.</p>'],
            ['How do I get started with an Industry SEO campaign?', '<p>Simply click “Get Free SEO Audit” or call/WhatsApp us. We’ll conduct a free audit of your website and top 3 industry competitors, then walk you through a custom 6-month growth roadmap with zero obligation.</p>'],
        ];
        $industrySeoIds = $this->upsertFaqGroup('seo-by-industry', $industrySeoFaqs, 130);
        if ($industrySeoPage = Page::where('slug', 'seo-by-industry')->first()) {
            $industrySeoPage->syncFaqs($industrySeoIds);
        }

        // 4. Fix and sync FAQs for imported pages (Noida, Ghaziabad, AI SEO India)
        $noidaDmFaqs = [
            ['What digital marketing services does Tech Alphonic offer in Noida?', '<p>Being one of the top digital marketing agencies in Noida, Tech Alphonic provides extensive digital marketing services including Search Engine Optimization (SEO), Social Media Optimization (SMO), Pay-Per-Click (PPC) Advertising, Content Marketing, Email Marketing, and Conversion Rate Optimization (CRO). Each service is crafted to enhance your online presence and create improved ROI.</p>'],
            ['How does Tech Alphonic enhance online visibility for businesses?', '<p>Tech Alphonic uses a data-driven strategy to provide greater visibility to your business online: we optimize websites for higher search engine rankings, compose effective content for your target audience, manage social media profiles to build brand presence, run targeted PPC campaigns to acquire traffic, and design customer retention strategies to convert visitors into customers.</p>'],
            ['Does Tech Alphonic offer customized digital marketing solutions?', '<p>Yes, Tech Alphonic offers customized digital marketing solutions designed specifically to fit each business’s needs and objectives. We guide businesses to best practices by optimizing their plans to achieve their outlined goals and reach their target audiences.</p>'],
            ['What industries does Tech Alphonic serve with its digital marketing services?', '<p>We provide digital marketing services to diverse industries including E-commerce, Healthcare, Education, Real Estate, Technology, Hospitality, Retail, Startups, and Enterprises, tailoring our solutions to each industry’s market dynamics.</p>'],
            ['How do I contact Tech Alphonic for digital marketing services?', '<p>You can reach us by calling +91 98991 84918, emailing info@techalphonic.com or sales@techalphonic.com, or visiting our office at C-203, C Block, Sector 63, Noida, Uttar Pradesh 201309.</p>'],
        ];
        $noidaIds = $this->upsertWithFixedIds([19, 20, 21], 'page-digital-marketing-company-in-noida', $noidaDmFaqs, 140);
        if ($p = Page::where('slug', 'digital-marketing-company-in-noida')->first()) {
            $p->syncFaqs($noidaIds);
        }

        $ghaziabadDmFaqs = [
            ['What makes Tech Alphonic different from other digital marketing companies in Ghaziabad?', '<p>We use advanced tools across SEO, social media, and PPC for a complete optimization strategy — focusing not just on rankings, but also on generative AI search visibility and multi-channel customer engagement in Ghaziabad.</p>'],
            ['Why is Tech Alphonic considered a top digital marketing agency in Ghaziabad?', '<p>Our professionals blend local NCR market expertise with global digital strategies to provide powerful, cost-effective digital marketing services in Ghaziabad for local, national, and international audiences alike.</p>'],
            ['How does Tech Alphonic structure content and keywords for Digital Marketing Services in Ghaziabad?', '<p>Through strategic research and natural placement of high-intent local and service keywords across headers, body copy, and metadata while keeping user readability and helpfulness as our top priority.</p>'],
            ['What role does digital marketing play in increasing the visibility of Ghaziabad-based companies?', '<p>We increase local rankings and connect brands with nearby customers through Google Business Profile optimization, local citation management, and conversational voice-search optimization for “near me” queries.</p>'],
            ['Does Tech Alphonic forecast and identify digital trends that can improve my business?', '<p>Yes, our predictive analytics detect early rising search and consumer patterns for Ghaziabad businesses, enabling campaigns that capture first-mover advantage in competitive local markets.</p>'],
        ];
        $ghzIds = $this->upsertWithFixedIds([22, 23, 24], 'page-digital-marketing-company-in-ghaziabad', $ghaziabadDmFaqs, 150);
        if ($p = Page::where('slug', 'digital-marketing-company-in-ghaziabad')->first()) {
            $p->syncFaqs($ghzIds);
        }

        $aiSeoIndiaFaqs = [
            ['What is the difference between an AI-Powered SEO Agency and traditional SEO?', '<p>As an AI-Powered SEO Agency, we infuse AI across every layer — from intent analysis to content engineering, technical audits, link outreach, and local optimization. We optimize not only for traditional web page rankings, but also for generative AI search engines (Google AI Overviews, ChatGPT, Gemini) and structured rich answers.</p>'],
            ['Why hire an AI SEO Company in India like Tech Alphonic?', '<p>Our specialists merge deep SEO domain expertise with modern AI tools to achieve both scale and quality. We offer competitive pricing, a clear understanding of business outcomes, and proven AI optimization strategies for Indian and international markets.</p>'],
            ['How do you balance keyword optimization with natural readability in AI SEO?', '<p>We use NLP and semantic analysis to place target concepts naturally across headers, paragraphs, and metadata while keeping reader experience, E-E-A-T, and helpfulness in focus.</p>'],
            ['How does AI make local SEO better?', '<p>AI enables more precise location-specific content, faster Google Business Profile optimization, accurate citation tracking, and conversational voice-search optimization for “near me” queries.</p>'],
            ['Can AI SEO help predict upcoming search trends?', '<p>Yes. Predictive analytics driven by AI helps identify rising keywords and topic clusters early so we can publish authoritative content before a trend peaks, giving clients a first-mover advantage.</p>'],
            ['What results can we expect from working with an AI SEO Company in India?', '<p>While no ethical agency guarantees a specific #1 slot, our AI-enabled methods significantly improve search visibility, AI citations, click-through rates, and qualified organic leads.</p>'],
        ];
        $aiSeoIds = $this->upsertWithFixedIds([25, 26, 27], 'landing-ai-seo-company-in-india', $aiSeoIndiaFaqs, 160);
        if ($lp = LandingPage::where('slug', 'ai-seo-company-in-india')->first()) {
            $lp->syncFaqs($aiSeoIds);
        }

        // 5. Service-Specific Authentic FAQs verbatim from old service pages
        $serviceFaqsMap = [
            'search-engine-optimization' => [
                ['How long does it take to see results from SEO?', '<p>SEO is a long-term strategy. Most businesses start seeing noticeable improvements in keyword rankings and organic traffic within 3 to 6 months, depending on your industry competition, current website health, and target keywords. Local SEO results often appear even faster (within 30–60 days).</p>'],
                ['Do you guarantee #1 ranking on Google?', '<p>No ethical SEO agency can guarantee a #1 spot because Google’s algorithm uses 200+ dynamic ranking factors. However, we guarantee 100% white-hat execution, measurable ranking improvements, and consistent growth in organic traffic and leads — backed by our 98% client retention rate.</p>'],
                ['What is the difference between On-Page, Off-Page, and Technical SEO?', '<p><strong>On-Page SEO</strong> optimizes content, keywords, and meta tags on your site. <strong>Technical SEO</strong> ensures search engines can crawl and index your site quickly (speed, mobile, schema). <strong>Off-Page SEO</strong> builds your site’s authority through high-quality backlinks and brand mentions across the web.</p>'],
                ['Can I cancel or upgrade my SEO plan anytime?', '<p>Yes! We don’t believe in locking clients into rigid long-term contracts. You can upgrade, downgrade, or pause your monthly SEO plan anytime with 30 days’ notice. Our results keep you with us — not paperwork.</p>'],
                ['What will I receive in my monthly SEO report?', '<p>Every month you receive a detailed report covering: keyword ranking changes, organic traffic &amp; user growth (via GA4 &amp; Search Console), backlinks created with live URLs, technical improvements made, and next month’s action plan.</p>'],
            ],
            'ecommerce-seo' => [
                ['How long does it take to see results from Ecommerce SEO?', '<p>Most online stores start seeing noticeable improvements in keyword rankings and organic impressions within 60–90 days. Significant revenue growth typically compounds between months 4 and 6 as product pages, category authority, and backlinks gain traction.</p>'],
                ['Which ecommerce platforms do you work with?', '<p>We work with all major platforms including Shopify, Shopify Plus, WooCommerce (WordPress), Magento (Adobe Commerce), BigCommerce, Wix Ecommerce, OpenCart, and custom-built headless storefronts (Next.js / React / Laravel).</p>'],
                ['How is Ecommerce SEO different from regular website SEO?', '<p>Ecommerce sites have thousands of URLs, dynamic filters, pagination, out-of-stock products, and thin manufacturer descriptions. Ecommerce SEO requires specialized technical architecture, product schema markup, and buyer-intent keyword mapping focused on checkouts rather than just blog reads.</p>'],
                ['Do you optimize individual product descriptions?', '<p>Yes! Duplicate manufacturer descriptions kill rankings. Our ecommerce copywriters craft unique, keyword-optimized, conversion-focused titles and descriptions for your top-selling and priority products and categories.</p>'],
                ['What happens when products go out of stock or are discontinued?', '<p>Deleting product pages causes 404 errors and loses valuable backlinks. We implement a proper SEO workflow — keeping seasonal pages live with restock alerts, or applying strategic 301 redirects to the closest alternative product or parent category.</p>'],
                ['How do you measure the ROI of Ecommerce SEO?', '<p>We configure GA4 Enhanced Ecommerce tracking and Google Search Console to track organic sessions, product page views, add-to-cart rate, checkout completions, and exact organic revenue generated month over month.</p>'],
            ],
            'ai-seo-services' => [
                ['What are AI SEO Services (AEO, GEO & LLM Optimization)?', '<p>AI SEO Services optimize your brand and website content so that generative AI platforms — including Google AI Overviews (SGE), ChatGPT, Google Gemini, Perplexity, and Microsoft Copilot — recognize, cite, and recommend your business when users ask questions in your domain.</p>'],
                ['Does AI SEO replace traditional Google SEO?', '<p>No — AI SEO builds on top of strong traditional SEO. Search engines and LLMs still rely on crawlable technical architecture, authoritative backlinks, structured schema markup, and high-E-E-A-T content to verify facts and cite sources.</p>'],
                ['How does Tech Alphonic optimize websites for ChatGPT, Gemini, and AI Overviews?', '<p>We combine entity-based content optimization, conversational Q&amp;A formatting, comprehensive JSON-LD schema markup, llms.txt readiness, and authoritative digital citations so AI models clearly understand and reference your brand.</p>'],
                ['Who benefits most from AI SEO services?', '<p>B2B companies, SaaS brands, healthcare providers, e-commerce stores, and service businesses whose customers research comparisons, recommendations, and “best company for…” queries inside AI assistants and Google AI Overviews.</p>'],
            ],
            'app-store-seo' => [
                ['What is App Store Optimization (ASO) and why do I need it?', '<p>ASO is the process of improving your mobile app’s visibility in app stores (like Google Play and Apple App Store) and increasing conversion rates from store page views to downloads. Since over 65% of apps are discovered via app store search, ASO is the most cost-effective way to get consistent, high-intent organic installs.</p>'],
                ['How long does it take to see results from ASO?', '<p>You can typically see initial keyword ranking movements within 2 to 4 weeks after implementing metadata changes. Significant growth in organic downloads and top-10 category rankings usually builds over 2 to 3 months of continuous optimization, A/B testing, and review management.</p>'],
                ['What is the difference between Google Play ASO and Apple App Store ASO?', '<p>Apple’s App Store uses a dedicated 100-character keyword field, title (30 chars), and subtitle (30 chars), and does not index the long description for keywords. Google Play indexes your title, short description (80 chars), and the full 4,000-character long description (similar to web SEO), plus backlinks play a role in Google Play rankings.</p>'],
                ['Can ASO reduce my paid user acquisition (UA) costs?', '<p>Absolutely. By optimizing your icons, screenshots, and ratings (Conversion Rate Optimization), more users who click your paid ads actually install the app. This directly lowers your Cost Per Install (CPI) on Apple Search Ads, Google App Campaigns, and Meta Ads.</p>'],
            ],
            'content-marketing' => [
                ['How long does it take to see results from content marketing?', '<p>Landing page and email copy improvements can lift conversions immediately, while SEO blog and pillar content typically builds steady organic traffic and leads over 60 to 120 days with compounding long-term ROI.</p>'],
                ['Do you use AI to write content, or is it written by human writers?', '<p>Every piece of content is researched with modern SEO/NLP tools and written and edited by our in-house human content writers and subject-matter editors to ensure accuracy, brand voice, originality, and Google E-E-A-T compliance.</p>'],
                ['Who owns the rights to the content you create for us?', '<p>You do. Upon delivery and payment, 100% of the copyright and ownership rights for all articles, website copy, graphics, and assets transfer directly to your business.</p>'],
                ['What if we don’t like the first draft of an article or copy?', '<p>Every content plan includes revision rounds. We share outlines and drafts with your team for feedback and refine the tone, structure, or details until you are completely satisfied before publishing.</p>'],
                ['Do you also upload and publish the content on our website / CMS?', '<p>Yes! We can directly format and publish approved articles on WordPress, Shopify, Webflow, or your custom CMS with proper headings, internal links, meta tags, featured graphics, alt text, and schema markup.</p>'],
                ['Can you write content for technical or niche B2B industries?', '<p>Yes. Our content team writes across SaaS, IT, healthcare, finance, legal, real estate, logistics, manufacturing, e-commerce, and hospitality — conducting deep subject-matter research and stakeholder interviews.</p>'],
                ['Can we pause, upgrade, or cancel our monthly content plan anytime?', '<p>Yes. All our monthly content retainers are flexible with no long-term lock-in contracts. You can scale your article volume up or down with 15 days’ notice before your next billing cycle.</p>'],
            ],
            'branding' => [
                ['How long does a complete branding or rebranding project take?', '<p>A starter logo and identity project typically takes 1 to 2 weeks, while a comprehensive brand strategy, guideline book, collateral kit, and 360° visual identity system takes 3 to 5 weeks depending on feedback cycles.</p>'],
                ['What is the difference between a logo design and a full brand identity?', '<p>A logo is just your primary mark. A full brand identity includes your logo system (primary, secondary, submark), color palette, typography hierarchy, brand voice, stationery, social media templates, and a comprehensive brand guideline book that keeps every touchpoint consistent.</p>'],
                ['Will we own 100% copyright and receive all source files?', '<p>Yes. Upon project completion, you receive 100% copyright ownership along with all master vector and digital source files (AI, EPS, SVG, PDF, and high-resolution PNG/JPG in color, black, white, and transparent formats).</p>'],
                ['How many concepts and revision rounds are included?', '<p>Depending on your chosen plan, we deliver 3 to 5 original logo and identity concepts created from scratch by our brand designers, followed by iterative revision rounds until you are 100% satisfied.</p>'],
                ['We already have an established business. How do you handle rebranding without losing our current customers?', '<p>We specialize in evolutionary and strategic rebranding — preserving the core brand equity and recognition your existing customers trust while modernizing your visual language, typography, and positioning for new growth.</p>'],
            ],
            'website-development' => [
                ['How long does it take to build a website?', '<p>A standard 5-page business website typically takes 7–14 days. Larger dynamic websites or e-commerce stores take 3–5 weeks depending on custom features, content readiness, and feedback cycles.</p>'],
                ['Will my website be mobile-friendly and SEO optimized?', '<p>Yes, 100%! Every website we build is mobile-first, responsive across all screen sizes, optimized for fast loading speeds (Core Web Vitals), and includes complete on-page SEO fundamentals out of the box.</p>'],
                ['Can I edit and update the website content myself after launch?', '<p>Absolutely. We build user-friendly admin dashboards (like WordPress or custom CMS) and provide a free walkthrough training session so you can easily edit text, images, blogs, and products without touching a line of code.</p>'],
                ['Do you provide hosting, domain, and post-launch maintenance?', '<p>Yes! We can help you set up high-speed hosting and domain registration, or deploy on your existing server. Every plan also includes free post-launch support, and we offer affordable annual maintenance packages (AMC).</p>'],
                ['What payment terms do you follow for web development projects?', '<p>We follow a transparent milestone-based structure: typically 50% advance to kick off design &amp; development, and 50% upon completion and final review before live deployment.</p>'],
            ],
            'laravel-development' => [
                ['Why should I choose Laravel over Node.js, Django, or WordPress for my web application?', '<p>Laravel offers the fastest time-to-market for custom web applications without sacrificing enterprise scalability. Unlike WordPress (which relies on third-party plugins that can slow down or break), Laravel gives you 100% custom logic, built-in enterprise security (CSRF, SQLi protection), native queue management, and effortless scaling to millions of requests.</p>'],
                ['How long does it take to build a custom Laravel application?', '<p>A standard MVP or business portal typically takes <strong>3 to 6 weeks</strong>. Mid-sized SaaS platforms or custom eCommerce stores take <strong>6 to 12 weeks</strong>, while large enterprise systems may take 3+ months depending on integrations and complexity. We provide a milestone-based timeline before starting.</p>'],
                ['Can you migrate our existing legacy PHP / CodeIgniter / WordPress site to Laravel?', '<p>Yes! Legacy migration is one of our core specialties. We audit your existing database and codebase, re-architect it in modern Laravel 11, migrate 100% of your data safely, and preserve all your URLs and SEO rankings with zero business downtime.</p>'],
                ['Will I get full source code ownership and intellectual property (IP) rights?', '<p>Absolutely. Upon project completion, 100% of the source code, Git repository, documentation, and intellectual property rights belong exclusively to you. We also sign strict NDAs before discussing your project idea.</p>'],
                ['Do you provide post-launch support and maintenance after the app goes live?', '<p>Yes. Every Laravel project includes complimentary post-launch warranty support (30 to 90 days depending on your plan) for bug fixes and server monitoring. After that, we offer flexible monthly maintenance and dedicated developer retainers.</p>'],
            ],
            'wordpress-development' => [
                ['How long does it take to build a custom WordPress website?', '<p>A standard 5–10 page custom business website typically takes <strong>2 to 3 weeks</strong>. A full-featured WooCommerce store or complex enterprise WordPress portal takes <strong>4 to 8 weeks</strong>, depending on custom features, integrations, and content readiness.</p>'],
                ['Do you use pre-made templates or build custom WordPress themes?', '<p>We specialize in <strong>100% custom-designed WordPress themes</strong> built from scratch in Figma and coded using clean PHP, Gutenberg blocks, or lightweight Elementor Pro structures. This ensures your site looks unique, loads fast, and stays free from bloated third-party code.</p>'],
                ['Will my WordPress website be mobile-responsive and SEO-friendly?', '<p>Yes, every website we deliver is mobile-first (tested across iOS, Android, tablets, and desktops) and comes pre-configured with technical SEO best practices — clean permalinks, fast Core Web Vitals, schema markup, XML sitemaps, and RankMath/Yoast setup.</p>'],
                ['Can I edit and update the website content myself without coding knowledge?', '<p>Absolutely! We build an intuitive, user-friendly admin backend using Gutenberg blocks or ACF fields so you can change text, swap images, add blog posts, or create new pages effortlessly. We also include a complimentary 1-on-1 video training session at launch.</p>'],
                ['Can you migrate my existing website from Wix, Shopify, or old HTML to WordPress?', '<p>Yes. We handle complete end-to-end migrations — transferring all your pages, blog posts, images, products, and customer data while setting up proper 301 redirects so you don’t lose a single drop of Google SEO traffic or rankings.</p>'],
            ],
            'react-js-development' => [
                ['Why should I choose React.js over other frontend frameworks?', '<p>React.js offers unmatched rendering speed via its Virtual DOM, a massive ecosystem of battle-tested libraries, reusable component architecture (saving up to 40% in development time), and seamless scalability from startup MVPs to enterprise apps with millions of users. Plus, pairing it with Next.js solves all SEO requirements.</p>'],
                ['Is React.js good for SEO? How do you handle Google indexing?', '<p>Standard client-side React SPAs can sometimes struggle with search crawlers, which is why we build SEO-critical websites using <strong>Next.js (Server-Side Rendering &amp; Static Site Generation)</strong>. This delivers pre-rendered HTML to Googlebot, achieving 95+ Lighthouse scores and top search rankings.</p>'],
                ['How long does it take to build a React.js web application?', '<p>A typical MVP or medium-complexity React application takes <strong>3 to 6 weeks</strong>. Larger enterprise SaaS platforms or full-stack web apps with custom backends usually take <strong>8 to 14 weeks</strong>. We work in agile 2-week sprints so you see live progress from week one.</p>'],
                ['Can you migrate our existing website (PHP / WordPress / Angular) to React.js?', '<p>Yes! We specialize in zero-downtime legacy migrations. We can either decouple your current CMS (like WordPress or Laravel) into a Headless React/Next.js frontend, or completely re-engineer your legacy app into a modern React stack while preserving all SEO URLs and data.</p>'],
            ],
            'shopify-development' => [
                ['How long does it take to build and launch a custom Shopify store?', '<p>A standard Shopify store with theme customization typically takes <strong>1 to 2 weeks</strong>. A completely bespoke custom-coded Shopify 2.0 theme or Shopify Plus store with ERP/CRM integrations usually takes <strong>3 to 6 weeks</strong> depending on catalog size and custom requirements.</p>'],
                ['Can you migrate my existing WooCommerce / Magento store to Shopify without losing SEO?', '<p>Yes, 100%. We handle full-scale migrations including products, variants, customer accounts, order history, blogs, and pages. Most importantly, we map every old URL to the new Shopify structure using <strong>301 redirects</strong> and preserve meta tags so you don’t lose your hard-earned Google rankings.</p>'],
                ['Will I be able to manage products, prices, and banners myself after launch?', '<p>Absolutely. We build all our themes using <strong>Shopify OS 2.0 drag-and-drop sections and metafields</strong>. You won’t need to touch a single line of code to add products, change banners, create new landing pages, or run sales. Plus, we include a recorded training session for your team.</p>'],
                ['Do you integrate Indian & international payment gateways and shipping partners?', '<p>Yes! For Indian stores, we integrate Razorpay, Cashfree, PayU, Easebuzz, Magic Checkout, Shiprocket, Delhivery, and BlueDart. For global D2C brands, we configure Shopify Payments, Stripe, PayPal, Klarna, DHL, FedEx, and multi-currency checkout.</p>'],
                ['What is the difference between Shopify and Shopify Plus? Which one do I need?', '<p>Standard Shopify plans (Basic, Shopify, Advanced) are ideal for 90% of startups and growing D2C brands. <strong>Shopify Plus</strong> is designed for high-volume enterprises doing $1M+ in annual revenue who need custom checkout logic, lower transaction fees, B2B wholesale channels, and dedicated expansion stores.</p>'],
            ],
            'ui-ux' => [
                ['What is the difference between UI and UX design, and do I need both?', '<p><strong>UX (User Experience)</strong> focuses on how a product works — user research, information architecture, wireframes, and seamless user flows. <strong>UI (User Interface)</strong> focuses on how it looks — colors, typography, icons, buttons, and visual polish. You need both working together: UX ensures users can achieve their goals effortlessly, while UI builds trust and emotional connection with your brand.</p>'],
                ['How long does a typical UI/UX design project take?', '<p>A landing page or 5-screen MVP typically takes <strong>1 to 2 weeks</strong>. A comprehensive web app or mobile app (15–25 screens) with user research, wireframes, interactive prototyping, and a design system usually takes <strong>3 to 5 weeks</strong>. We provide a clear milestone schedule before kicking off.</p>'],
                ['What deliverables will I receive at the end of the project?', '<p>You receive 100% ownership of organized <strong>Figma source files</strong> containing: User personas &amp; journey maps, low-fidelity wireframes, high-fidelity desktop &amp; mobile screens, a clickable interactive prototype, a reusable Design System / UI Kit (colors, fonts, components, states), and exported SVG/PNG assets ready for your developers.</p>'],
                ['Can you also develop (code) the designs after the UI/UX phase is approved?', '<p>Yes! As a full-service digital agency, our in-house frontend and full-stack developers (React, Next.js, WordPress, Flutter, iOS/Android) can bring our Figma designs to life with pixel-perfect accuracy — or we can collaborate directly with your internal engineering team.</p>'],
            ],
        ];

        $offset = 200;
        foreach ($serviceFaqsMap as $serviceSlug => $faqPairs) {
            $ids = $this->upsertFaqGroup('service-'.$serviceSlug, $faqPairs, $offset);
            $offset += 20;
            if ($svc = Service::where('slug', $serviceSlug)->first()) {
                $svc->syncFaqs($ids);
            }
        }

        // For remaining services that share core SEO / marketing / dev FAQs, ensure they have relevant FAQs
        $defaultMarketingIds = Service::where('slug', 'search-engine-optimization')->first()?->faqs()->pluck('faqs.id')->all() ?? [];
        $defaultDevIds = Service::where('slug', 'website-development')->first()?->faqs()->pluck('faqs.id')->all() ?? [];

        foreach (['local-seo', 'technical-seo', 'on-page-seo', 'off-page-seo-services', 'social-media', 'pay-per-click'] as $slug) {
            if ($svc = Service::where('slug', $slug)->first()) {
                $svc->syncFaqs($defaultMarketingIds);
            }
        }
        foreach (['custom-web-development', 'app-development'] as $slug) {
            if ($svc = Service::where('slug', $slug)->first()) {
                $svc->syncFaqs($defaultDevIds);
            }
        }

        ServiceCategory::where('slug', 'digital-marketing')->first()?->syncFaqs(array_slice($generalIds, 0, 5));
        ServiceCategory::where('slug', 'development')->first()?->syncFaqs($defaultDevIds);
    }

    private function upsertFaqGroup(string $categorySlug, array $pairs, int $baseOrder): array
    {
        $cat = FaqCategory::updateOrCreate(
            ['slug' => $categorySlug],
            ['name' => Str::headline($categorySlug), 'sort_order' => $baseOrder]
        );
        $ids = [];
        foreach ($pairs as $i => [$q, $a]) {
            $faq = Faq::updateOrCreate(
                ['question' => $q],
                [
                    'faq_category_id' => $cat->id,
                    'answer' => $a,
                    'is_active' => true,
                    'sort_order' => $baseOrder + $i,
                ]
            );
            $ids[] = $faq->id;
        }

        return $ids;
    }

    private function upsertWithFixedIds(array $fixedIds, string $categorySlug, array $pairs, int $baseOrder): array
    {
        $cat = FaqCategory::updateOrCreate(
            ['slug' => $categorySlug],
            ['name' => Str::headline($categorySlug), 'sort_order' => $baseOrder]
        );
        $ids = [];
        foreach ($pairs as $i => [$q, $a]) {
            $fixedId = $fixedIds[$i] ?? null;
            $faq = ($fixedId ? Faq::find($fixedId) : null) ?? Faq::where('question', $q)->first();
            if ($faq) {
                $faq->update([
                    'faq_category_id' => $cat->id,
                    'question' => $q,
                    'answer' => $a,
                    'is_active' => true,
                    'sort_order' => $baseOrder + $i,
                ]);
                $ids[] = $faq->id;
            } else {
                $created = Faq::create([
                    'faq_category_id' => $cat->id,
                    'question' => $q,
                    'answer' => $a,
                    'is_active' => true,
                    'sort_order' => $baseOrder + $i,
                ]);
                $ids[] = $created->id;
            }
        }

        return $ids;
    }
}
