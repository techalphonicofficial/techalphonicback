<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $publish = ['status' => ContentStatus::Published, 'published_at' => now()];

        $about = Page::updateOrCreate(['slug' => 'about-us'], $publish + [
            'title' => 'About Us', 'template' => 'about', 'hero_layout' => 'split',
            'h1' => 'We help businesses grow with technology & marketing',
            'hero_subheading' => 'Your Trusted IT Partner',
            'excerpt' => 'Tech Alphonic is a Noida-based digital agency bringing SEO, performance marketing and software engineering together under one roof.',
            'content' => '<p>Tech Alphonic was founded with a simple idea: growth works best when marketing and technology are planned together. Too many businesses juggle an SEO agency, an ads freelancer and a separate development vendor — and nobody owns the outcome.</p><h2>What we believe</h2><ul><li><strong>Outcomes over outputs.</strong> We measure success in leads, sales and efficiency.</li><li><strong>Transparency.</strong> Clear scopes, honest timelines and reporting you can understand.</li><li><strong>Craft.</strong> Clean code, thoughtful design and white-hat marketing that lasts.</li></ul><h2>How we work</h2><p>Every client gets a dedicated point of contact supported by specialists in SEO, paid media, content, design and engineering. We start with discovery, agree on KPIs and then execute in focused sprints.</p>',
        ]);
        $about->syncBlocks([
            ['type' => 'statistics', 'data' => ['heading' => 'Tech Alphonic in numbers', 'background' => 'mist', 'items' => [
                ['value' => '250', 'suffix' => '+', 'title' => 'Projects delivered'], ['value' => '120', 'suffix' => '+', 'title' => 'Happy clients'],
                ['value' => '15', 'suffix' => '+', 'title' => 'Specialists'], ['value' => '8', 'suffix' => '+', 'title' => 'Years of experience'],
            ]]],
            ['type' => 'feature_cards', 'data' => ['heading' => 'Why businesses choose us', 'background' => 'white', 'items' => [
                ['title' => 'One accountable team', 'icon' => 'users', 'text' => 'Marketing and engineering under one roof — no finger-pointing.'],
                ['title' => 'Transparent reporting', 'icon' => 'chart', 'text' => 'Dashboards and monthly reviews tied to real business KPIs.'],
                ['title' => 'Built to perform', 'icon' => 'zap', 'text' => 'Fast, secure, SEO-ready websites as standard.'],
            ]]],
            ['type' => 'testimonials', 'data' => ['heading' => 'What clients say', 'background' => 'mist', 'limit' => 3]],
        ]);

        $contact = Page::updateOrCreate(['slug' => 'contact'], $publish + [
            'title' => 'Contact Us', 'template' => 'contact', 'hero_layout' => 'minimal',
            'h1' => 'Let’s talk about your project',
            'excerpt' => 'Call, WhatsApp or send us a message — we reply within one business day.',
        ]);
        $contact->syncFaqs(Faq::whereHas('category', fn ($q) => $q->where('slug', 'general'))->orderBy('sort_order')->limit(4)->pluck('id')->all());

        // Plans and prices exactly as published on the old website's Packages page.
        $plan = fn ($title, $value, $text, $features, $featured = false) => ['title' => $title, 'value' => $value, 'suffix' => '/month', 'text' => $text, 'features' => implode("\n", $features), 'featured' => $featured, 'url' => '#enquiry-popup'];
        $tiers = [
            'Website development' => [
                $plan('Starter', '₹9,999', 'Best for small businesses & startups', ['Up to 5 pages', 'Mobile responsive design', 'Contact form integration', 'Basic SEO setup', 'SSL certificate']),
                $plan('Growth', '₹19,999', 'Best for growing businesses', ['Up to 15 pages', 'Premium responsive design', 'Advanced contact forms', 'Full SEO optimisation', 'SSL + security setup', 'Blog / CMS integration', 'Custom animations & effects', '3 months free support'], true),
                $plan('Enterprise', '₹39,999', 'Best for large businesses & e-commerce', ['Unlimited pages', 'Custom web application', 'E-commerce integration', 'Advanced SEO + analytics', 'Payment gateway setup', 'Admin dashboard / portal', 'Custom integrations & APIs', '6 months priority support']),
            ],
            'SEO' => [
                $plan('Basic SEO', '₹9,999', 'For small businesses and local shops starting their online journey', ['10 target keywords', 'On-page SEO optimisation', 'Google Business Profile setup', '5 blog articles / month', 'Technical SEO audit', 'Monthly report']),
                $plan('Growth SEO', '₹19,999', 'For growing businesses that want serious rankings and organic traffic', ['30 target keywords', 'Advanced on-page + off-page SEO', 'Link building (20 high-DA backlinks)', '12 blog articles / month', 'Competitor analysis', 'Core Web Vitals optimisation', 'Weekly ranking reports', 'Google Analytics + Search Console'], true),
                $plan('Enterprise SEO', '₹39,999', 'Full-scale SEO authority for large businesses & e-commerce brands', ['Unlimited keywords', 'Full technical SEO + schema markup', '50+ high-DA backlinks / month', 'E-commerce SEO (product + category)', 'AI SEO strategy', '20 blog articles / month', 'Dedicated SEO manager', 'Bi-weekly strategy calls']),
            ],
            'Social media' => [
                $plan('Basic Social', '₹9,999', 'Build brand presence on top social platforms with consistent content', ['2 platforms (Facebook + Instagram)', '12 posts / month', 'Custom graphics design', 'Caption writing', 'Monthly performance report']),
                $plan('Growth Social', '₹19,999', 'Engage your audience, grow followers and run targeted ad campaigns', ['4 platforms (FB, IG, LinkedIn, X)', '24 posts + 8 reels / month', 'Ad campaign management (up to ₹20K spend)', 'Community management', 'Hashtag research + strategy', 'Weekly reports', 'Story creation'], true),
                $plan('Premium Social', '₹39,999', 'Full social media management with influencer outreach and advanced analytics', ['All major platforms + YouTube', '40 posts + 16 reels + YouTube videos', 'Ad management (up to ₹50K spend)', 'Influencer outreach', 'UGC content strategy', 'Dedicated social manager', 'Bi-weekly strategy calls']),
            ],
            'PPC / Google Ads' => [
                $plan('Basic Ads', '₹9,999', 'Get started with Google Ads and drive immediate traffic to your website', ['Search ad campaigns', 'Ad spend up to ₹25,000', 'Keyword research & selection', 'Ad copywriting', 'Monthly report']),
                $plan('Performance Ads', '₹19,999', 'Multi-channel PPC campaigns with remarketing to maximise conversions', ['Search + Display + Shopping ads', 'Ad spend up to ₹75,000', 'Remarketing campaigns', 'Landing page optimisation', 'Conversion tracking setup', 'A/B testing', 'Weekly reports'], true),
                $plan('Max Performance', '₹39,999', 'Advanced PPC management for high-budget campaigns across all channels', ['Google + Meta + LinkedIn ads', 'Ad spend up to ₹2,00,000', 'Performance Max campaigns', 'YouTube video ads', 'Custom audience building', 'Dedicated ads manager', 'Daily optimisation + reports']),
            ],
            'Content marketing' => [
                $plan('Blog & Articles', '₹9,999', 'Regular SEO-optimised content to drive organic traffic', ['8 SEO blog articles / month', 'Keyword-targeted writing', 'Meta descriptions', '1,000–1,500 words / article']),
                $plan('Full Content Suite', '₹19,999', 'Complete content strategy with blogs, infographics and email newsletters', ['16 SEO blog articles / month', '4 infographics', 'Email newsletter (4 / month)', 'Content calendar', 'Social media captions'], true),
                $plan('Brand Authority', '₹39,999', 'Brand storytelling, thought-leadership content and press releases', ['30 pieces of content / month', 'Whitepapers & case studies', 'Press releases', 'Video scripts', 'Dedicated content strategist']),
            ],
            'Branding & design' => [
                $plan('Logo & Identity', '₹9,999', 'Professional logo and basic brand identity kit for new businesses', ['3 logo concepts', 'Colour palette selection', 'Typography selection', 'Business card design', 'All source files included']),
                $plan('Full Brand Kit', '₹19,999', 'Complete brand identity system for a consistent, professional look', ['5 logo concepts + revisions', 'Brand guidelines document', 'Social media templates (10)', 'Letterhead + envelope design', 'Email signature design'], true),
                $plan('Corporate Identity', '₹39,999', '360° brand system for established companies rebranding or scaling', ['Unlimited logo revisions', 'Complete brand bible (50+ pages)', 'Marketing collateral design', 'Packaging design', 'Brand strategy consultation']),
            ],
            'Combo' => [
                $plan('Digital Kickstart', '₹9,999', 'SEO + social media + content — everything to establish your digital presence', ['SEO (10 keywords)', 'Social media (2 platforms, 16 posts)', '8 blog articles / month', 'Monthly performance dashboard', 'Single dedicated manager']),
                $plan('360° Digital Growth', '₹19,999', 'Complete digital marketing: SEO + social + PPC + content', ['SEO (30 keywords + link building)', 'Social media (4 platforms, 24 posts + reels)', 'Google Ads (up to ₹50K spend)', '12 blog articles + 4 infographics', 'Dedicated account manager', 'Weekly reports + monthly strategy call'], true),
                $plan('Full IT Partner', '₹39,999', 'Website + full digital marketing + branding — your complete IT partner', ['Full enterprise SEO', 'All-platform social media', 'Multi-channel PPC (up to ₹2L spend)', 'Website development + maintenance', 'Full content marketing', 'Branding & design support', 'Dedicated team + CXO access']),
            ],
        ];
        $blocks = [];
        foreach ($tiers as $heading => $items) {
            $blocks[] = ['type' => 'pricing', 'data' => ['heading' => $heading.' packages', 'background' => count($blocks) % 2 ? 'mist' : 'white', 'items' => $items]];
        }
        $blocks[] = ['type' => 'feature_cards', 'data' => ['heading' => 'Optional add-ons', 'subheading' => 'Enhance any package with add-ons tailored to your business.', 'background' => 'white', 'items' => [
            ['title' => 'App development', 'text' => 'Android & iOS apps from ₹49,999', 'icon' => 'smartphone'],
            ['title' => 'AI chatbot integration', 'text' => 'Custom chatbot for your website from ₹9,999', 'icon' => 'message'],
            ['title' => 'App Store SEO', 'text' => 'Boost app visibility from ₹4,999/mo', 'icon' => 'trending-up'],
            ['title' => 'Email marketing', 'text' => 'Campaigns + automation from ₹5,999/mo', 'icon' => 'mail'],
            ['title' => 'ERP / CRM setup', 'text' => 'Business automation from ₹29,999', 'icon' => 'layers'],
            ['title' => 'Security & maintenance', 'text' => 'Monthly website care from ₹2,999/mo', 'icon' => 'shield'],
            ['title' => 'UI/UX design', 'text' => 'Wireframes & prototypes from ₹14,999', 'icon' => 'layout'],
            ['title' => 'Video production', 'text' => 'Reels and explainer videos from ₹7,999', 'icon' => 'play'],
        ]]];
        $blocks[] = ['type' => 'process', 'data' => ['heading' => 'Get started in 4 simple steps', 'background' => 'mist', 'items' => [
            ['title' => 'Free consultation', 'text' => 'Tell us about your business goals. We analyse your current digital presence for free.'],
            ['title' => 'Choose your plan', 'text' => 'Pick a package that fits your budget and business needs — we will recommend the best fit.'],
            ['title' => 'Onboarding & strategy', 'text' => 'We set everything up and create a custom 90-day roadmap for your growth.'],
            ['title' => 'Grow & scale', 'text' => 'Track results through regular reports and watch your business grow every month.'],
        ]]];
        $blocks[] = ['type' => 'cta', 'data' => ['heading' => 'Not sure which package fits?', 'subheading' => 'Get a free consultation and a custom quote. Month-to-month plans, no hidden fees.', 'cta_text' => 'Get a custom quote', 'cta_url' => '#enquiry-popup', 'background' => 'dark']];

        Page::updateOrCreate(['slug' => 'packages'], $publish + [
            'title' => 'Packages & Pricing', 'template' => 'default', 'hero_layout' => 'centered',
            'h1' => 'Affordable packages for every business goal',
            'excerpt' => 'From startups to enterprises — choose the right plan across SEO, digital marketing, web development, social media and more. No hidden charges. Every package includes a dedicated account manager.',
        ])->syncBlocks($blocks);


        Page::updateOrCreate(['slug' => 'seo-by-industry'], $publish + [
            'title' => 'SEO by Industry', 'template' => 'default', 'hero_layout' => 'centered',
            'h1' => 'SEO services by industry',
            'excerpt' => 'Every industry has its own search behaviour. Explore how we approach SEO for your sector.',
        ])->syncBlocks([
            ['type' => 'industries', 'data' => ['heading' => 'Industries we specialise in', 'background' => 'white', 'limit' => 20]],
            ['type' => 'cta', 'data' => ['heading' => 'Don’t see your industry?', 'subheading' => 'We have likely worked with businesses like yours — let’s talk.', 'cta_text' => 'Talk to an expert', 'background' => 'dark']],
        ]);

        foreach ([
            ['privacy-policy', 'Privacy Policy', '<p><em>Template — have this reviewed by your legal advisor before publishing.</em></p><h2>Information we collect</h2><p>When you submit an enquiry we collect your name, phone number, email address and the details you provide. We also collect standard analytics data such as pages visited.</p><h2>How we use it</h2><p>We use your information only to respond to your enquiry, provide our services and improve our website. We do not sell your personal data.</p><h2>Cookies</h2><p>We use cookies for analytics and to remember preferences. You can control cookies in your browser settings.</p><h2>Contact</h2><p>For privacy questions email info@techalphonic.com.</p>'],
            ['terms-condition', 'Terms & Conditions', '<p><em>Template — have this reviewed by your legal advisor before publishing.</em></p><h2>Use of this website</h2><p>Content on this website is for general information. Service engagements are governed by a separate written agreement.</p><h2>Intellectual property</h2><p>All content, logos and designs are the property of Tech Alphonic unless stated otherwise.</p><h2>Limitation of liability</h2><p>We are not liable for losses arising from the use of information on this website.</p>'],
        ] as [$slug, $title, $content]) {
            Page::updateOrCreate(['slug' => $slug], $publish + ['title' => $title, 'template' => 'legal', 'hero_layout' => 'minimal', 'content' => $content, 'show_in_search' => false]);
        }
    }
}
