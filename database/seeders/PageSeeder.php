<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    private const BLOCK_STATISTICS = 'statistics';
    private const BLOCK_FEATURE_CARDS = 'feature_cards';
    private const BLOCK_TESTIMONIALS = 'testimonials';
    private const BLOCK_PRICING = 'pricing';
    private const BLOCK_PROCESS = 'process';
    private const BLOCK_CTA = 'cta';
    private const BLOCK_INDUSTRIES = 'industries';

    public function run(): void
    {
        $this->page('about-us', 'About Us', 'Empowering [[Digital Growth]]', 'about', [
            'hero_subheading' => 'Quality Marketing Solutions · 10+ Years of Trust',
            'excerpt' => 'At Tech Alphonic, we transform your vision into digital success for the business. Being one of the most reputed IT solutions providers, we bring over 10 years of industry knowledge in providing custom-made services in Web & App Development, Digital Marketing, ERP Solutions, and other domains. Our seasoned professionals take an innovative, holistic approach to projects so that we do not merely satisfy your expectations, we exceed them.',
            'content' => <<<'HTML'
<h2>About Tech Alphonic — Transforming Your Vision Into Digital Success</h2>
<p>At Tech Alphonic, we transform your vision into digital success for the business. Being one of the most reputed IT solutions providers, we bring over 10 years of industry knowledge in providing custom-made services in Web &amp; App Development, Digital Marketing, ERP Solutions, and other domains. Our seasoned professionals take an innovative, holistic approach to projects so that we do not merely satisfy your expectations, we exceed them.</p>

<h2>Pathway to Success: Elevating Progress With Results-Oriented Strategies</h2>
<p>At Tech Alphonic, we leverage cutting-edge solutions to propel your business forward. Using the latest communication technology, we foster seamless interactions and build dynamic designs that captivate and convert. Our innovative engagement solutions are tailored to create meaningful connections and drive sustained growth for your brand.</p>
<ul>
    <li><strong>Latest Communication Technology:</strong> Seamless collaboration, real-time updates, and transparent reporting across every project stage.</li>
    <li><strong>Dynamic Design Approach:</strong> Modern, conversion-focused interfaces and responsive experiences built to captivate your audience.</li>
    <li><strong>Innovative Engagement Solutions:</strong> Tailored marketing and technology strategies designed to create meaningful connections and sustained growth.</li>
</ul>

<h2>Why Choose an AI-Driven Digital Marketing Agency?</h2>
<p>The digital marketing space is changing fast — old-school guesswork is out. Modern businesses need intelligent insights to stay ahead of the competition. By working with an AI-powered digital marketing company like Tech Alphonic, brands can unlock new opportunities for growth:</p>
<ul>
    <li><strong>Analyze Customer Behavior:</strong> Understand search intent, browsing patterns, and conversion triggers with deeper precision.</li>
    <li><strong>Improve Marketing Efficiency:</strong> Focus budgets and creative effort on the channels, keywords, and audiences that deliver real ROI.</li>
    <li><strong>Deliver Personalized Experiences:</strong> Tailor messaging, content, and user journeys to what your buyers actually care about.</li>
</ul>

<h2>How Does AI Upshift Digital Marketing Outcomes?</h2>
<p>Artificial intelligence is changing the whole marketing playbook for companies right now. By blending AI tools with strategic marketing thinking, we help brands make smarter calls and hit better results through <strong>advanced data analysis</strong>, <strong>customer journey mapping</strong>, <strong>predictive marketing</strong>, and <strong>automated campaign management</strong>.</p>
HTML,
            'meta_title' => 'About Tech Alphonic — Empowering Digital Growth | IT & Digital Marketing Partner',
            'meta_description' => 'At Tech Alphonic, we transform your vision into digital success with 10+ years of trust, 1500+ projects completed, and 60+ team members in Web & App Development and Digital Marketing.',
        ], [
            [self::BLOCK_STATISTICS, [
                'heading' => 'Tech Alphonic in numbers',
                'background' => 'mist',
                'items' => [
                    ['value' => '10', 'suffix' => '+', 'title' => 'Years of Trust'],
                    ['value' => '1500', 'suffix' => '+', 'title' => 'Projects Completed'],
                    ['value' => '60', 'suffix' => '+', 'title' => 'Team Members'],
                    ['value' => '100', 'suffix' => '%', 'title' => 'Client Satisfaction'],
                ],
            ]],
            [self::BLOCK_FEATURE_CARDS, [
                'heading' => 'Our Expertise',
                'subheading' => 'Empowering Digital Growth through tailored development and marketing solutions.',
                'background' => 'white',
                'items' => [
                    ['title' => 'Web Development', 'icon' => 'code', 'text' => 'At Tech Alphonic, we design and develop dynamic, user-friendly websites that reflect your brand identity and engage your target audience. From responsive business websites to complex e-commerce platforms, we ensure seamless performance and scalability.'],
                    ['title' => 'App Development', 'icon' => 'smartphone', 'text' => 'We specialize in crafting intuitive, high-performing mobile applications for Android and iOS platforms. Our custom app solutions are designed to streamline operations, enhance customer engagement, and drive business growth.'],
                    ['title' => 'Digital Marketing', 'icon' => 'chart', 'text' => 'We offer tailored digital marketing solutions that boost your online presence, attract targeted traffic, and maximize ROI. From SEO and PPC to social media marketing, we help your brand stand out in the competitive digital landscape.'],
                    ['title' => 'Enticing Content Creation', 'icon' => 'pen', 'text' => 'We craft compelling, original content that resonates with your audience and drives action. Whether it’s website copy, blogs, or social media posts, our content strategies are built to inform, inspire, and convert.'],
                    ['title' => 'Latest Communication Technology', 'icon' => 'cpu', 'text' => 'Using the latest communication technology, we foster seamless interactions, real-time analytics, and agile delivery across every digital touchpoint.'],
                    ['title' => 'Innovative Engagement Solutions', 'icon' => 'sparkles', 'text' => 'Our innovative engagement solutions are tailored to create meaningful customer connections and drive sustained, results-oriented growth for your brand.'],
                ],
            ]],
            [self::BLOCK_TESTIMONIALS, ['heading' => 'What Our Clients Say About Us', 'background' => 'mist', 'limit' => 3]],
        ]);

        $this->page('contact', 'Contact Us', 'Let’s talk about [[your project]]', 'contact', [
            'hero_subheading' => 'Contact Tech Alphonic',
            'excerpt' => 'Give us a call or drop by anytime, we endeavour to answer all enquiries within 24 hours on business days. We will be happy to answer your questions.',
            'content' => '<p>Looking for an experienced digital marketing, SEO, web, or mobile app development partner? Share your business goals with our team at C-203, C Block, Sector 63, Hazratpur Wajidpur, Noida, Uttar Pradesh 201309 and receive a customized strategy and free consultation within 24 hours on business days.</p>',
            'meta_title' => 'Contact Tech Alphonic — Digital Marketing & Web Development in Noida',
            'meta_description' => 'Contact Tech Alphonic Pvt. Ltd. at C-203, Sector 63, Noida. Call +91 98991 84918 or email info@techalphonic.com for a free consultation.',
        ]);

        // All 7 package categories, 8 optional add-ons, and 4-step process verbatim from https://seashell-stork-114708.hostingersite.com/packages/
        $this->page('packages', 'Packages & Pricing', 'Affordable Packages for [[Every Business Goal]]', 'packages', [
            'hero_subheading' => 'Transparent & Honest Pricing',
            'excerpt' => 'Transparent, results-oriented pricing — choose the best plan across SEO, Social Media, Website Development, Paid Ads, or custom hybrid packages. Switch or cancel anytime.',
            'content' => '',
            'meta_title' => 'Packages & Pricing — Website, SEO, Social Media, PPC & Branding | Tech Alphonic',
            'meta_description' => 'From startups to enterprises — choose the right plan across SEO, Digital Marketing, Web Development, Social Media & more. No hidden charges. Real results.',
        ], [
            [self::BLOCK_STATISTICS, [
                'heading' => '',
                'background' => 'white',
                'items' => [
                    ['value' => '10', 'suffix' => '+', 'title' => 'Years of Experience'],
                    ['value' => '1.5k', 'suffix' => '', 'title' => 'Projects Completed'],
                    ['value' => '60', 'suffix' => '+', 'title' => 'Team of Experts'],
                    ['value' => '100', 'suffix' => '%', 'title' => 'Satisfied Clients'],
                ],
            ]],
            [self::BLOCK_PRICING, [
                'heading' => 'Website Development Packages',
                'subheading' => 'All-inclusive plans with no hidden fees. Switch or cancel anytime. Every package includes a dedicated account manager.',
                'background' => 'white',
                'items' => [
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
            ]],
            [self::BLOCK_PRICING, [
                'heading' => 'SEO Packages',
                'subheading' => 'Rank higher on Google, drive organic traffic, and generate consistent inbound leads.',
                'background' => 'mist',
                'items' => [
                    [
                        'title' => 'Basic SEO',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Perfect for small businesses and local shops starting their online journey.',
                        'featured' => false,
                        'features' => implode("\n", [
                            '10 Target Keywords',
                            'On-Page SEO Optimization',
                            'Google My Business Setup',
                            '5 Blog Articles/Month',
                            'Technical SEO Audit',
                            'Monthly Report',
                            'Link Building Campaign',
                            'E-commerce SEO',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Growth SEO',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'For growing businesses that want serious rankings and organic traffic.',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            '30 Target Keywords',
                            'Advanced On-Page + Off-Page SEO',
                            'Link Building (20 High-DA Backlinks)',
                            '12 Blog Articles/Month',
                            'Competitor Analysis',
                            'Core Web Vitals Optimization',
                            'Weekly Ranking Reports',
                            'Google Analytics + Search Console',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Enterprise SEO',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'Full-scale SEO authority for large businesses & e-commerce brands.',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Unlimited Keywords',
                            'Full Technical SEO + Schema Markup',
                            '50+ High-DA Backlinks/Month',
                            'E-commerce SEO (Product + Category)',
                            'AI SEO Strategy',
                            '20 Blog Articles/Month',
                            'Dedicated SEO Manager',
                            'Bi-Weekly Strategy Calls',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
            ]],
            [self::BLOCK_PRICING, [
                'heading' => 'Social Media Packages',
                'subheading' => 'Build brand awareness, engage your community, and turn followers into loyal buyers.',
                'background' => 'white',
                'items' => [
                    [
                        'title' => 'Basic Social',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Build brand presence on top social platforms with consistent content.',
                        'featured' => false,
                        'features' => implode("\n", [
                            '2 Platforms (FB + Instagram)',
                            '12 Posts/Month',
                            'Custom Graphics Design',
                            'Caption Writing',
                            'Monthly Performance Report',
                            'Paid Ad Management',
                            'Reels / Video Content',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Growth Social',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'Engage your audience, grow followers, and run targeted ad campaigns.',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            '4 Platforms (FB, IG, LinkedIn, X)',
                            '24 Posts + 8 Reels/Month',
                            'Ad Campaign Management (up to ₹20K spend)',
                            'Community Management',
                            'Hashtag Research + Strategy',
                            'Weekly Reports',
                            'Story Creation',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Premium Social',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'Full social media management with influencer outreach and advanced analytics.',
                        'featured' => false,
                        'features' => implode("\n", [
                            'All Major Platforms + YouTube',
                            '40 Posts + 16 Reels + YouTube Videos',
                            'Ad Management (up to ₹50K spend)',
                            'Influencer Outreach',
                            'UGC Content Strategy',
                            'Dedicated Social Manager',
                            'Bi-Weekly Strategy Calls',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
            ]],
            [self::BLOCK_PRICING, [
                'heading' => 'PPC / Google Ads Packages',
                'subheading' => 'Instant leads, targeted traffic, and measurable ROAS on Google & Meta Ads.',
                'background' => 'mist',
                'items' => [
                    [
                        'title' => 'Basic Ads',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Get started with Google Ads and drive immediate traffic to your website.',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Search Ad Campaigns',
                            'Ad Spend up to ₹25,000',
                            'Keyword Research & Selection',
                            'Ad Copywriting',
                            'Monthly Report',
                            'Display Ads',
                            'Remarketing',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Performance Ads',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'Multi-channel PPC campaigns with remarketing to maximize conversions.',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            'Search + Display + Shopping Ads',
                            'Ad Spend up to ₹75,000',
                            'Remarketing Campaigns',
                            'Landing Page Optimization',
                            'Conversion Tracking Setup',
                            'A/B Testing',
                            'Weekly Reports',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Max Performance',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'Advanced PPC management for high-budget campaigns across all channels.',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Google + Meta + LinkedIn Ads',
                            'Ad Spend up to ₹2,00,000',
                            'Performance Max Campaigns',
                            'YouTube Video Ads',
                            'Custom Audience Building',
                            'Dedicated Ads Manager',
                            'Daily Optimization + Reports',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
            ]],
            [self::BLOCK_PRICING, [
                'heading' => 'Content Marketing Packages',
                'subheading' => 'Regular SEO-optimized content, infographics, newsletters, and thought leadership.',
                'background' => 'white',
                'items' => [
                    [
                        'title' => 'Blog & Articles',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Regular SEO-optimized content to drive organic traffic.',
                        'featured' => false,
                        'features' => implode("\n", [
                            '8 SEO Blog Articles/Month',
                            'Keyword-Targeted Writing',
                            'Meta Descriptions',
                            '1000-1500 Words/Article',
                            'Infographic Design',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Full Content Suite',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'Complete content strategy with blogs, infographics, and email newsletters.',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            '16 SEO Blog Articles/Month',
                            '4 Infographics',
                            'Email Newsletter (4/month)',
                            'Content Calendar',
                            'Social Media Captions',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Brand Authority',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'Full brand storytelling, thought leadership content, and press releases.',
                        'featured' => false,
                        'features' => implode("\n", [
                            '30 Pieces of Content/Month',
                            'Whitepapers & Case Studies',
                            'Press Releases',
                            'Video Scripts',
                            'Dedicated Content Strategist',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
            ]],
            [self::BLOCK_PRICING, [
                'heading' => 'Branding & Design Packages',
                'subheading' => 'Professional logo, brand guidelines, collateral, and 360° corporate identity systems.',
                'background' => 'mist',
                'items' => [
                    [
                        'title' => 'Logo & Identity',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'Professional logo and basic brand identity kit for new businesses.',
                        'featured' => false,
                        'features' => implode("\n", [
                            '3 Logo Concepts',
                            'Color Palette Selection',
                            'Typography Selection',
                            'Business Card Design',
                            'All Source Files Included',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Full Brand Kit',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'Complete brand identity system for a consistent, professional look.',
                        'featured' => true,
                        'badge' => 'Most Popular',
                        'features' => implode("\n", [
                            '5 Logo Concepts + Revisions',
                            'Brand Guidelines Document',
                            'Social Media Templates (10)',
                            'Letterhead + Envelope Design',
                            'Email Signature Design',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Corporate Identity',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => '360° brand system for established companies rebranding or scaling.',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Unlimited Logo Revisions',
                            'Complete Brand Bible (50+ pages)',
                            'Marketing Collateral Design',
                            'Packaging Design',
                            'Brand Strategy Consultation',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
            ]],
            [self::BLOCK_PRICING, [
                'heading' => 'Combo Packages',
                'subheading' => 'All-in-one digital marketing, website, and branding bundles for maximum ROI.',
                'background' => 'white',
                'items' => [
                    [
                        'title' => 'Digital Kickstart',
                        'value' => '₹9,999',
                        'suffix' => '/mo',
                        'text' => 'SEO + Social Media + Content — everything to establish your digital presence.',
                        'featured' => false,
                        'features' => implode("\n", [
                            'SEO (10 Keywords)',
                            'Social Media (2 Platforms, 16 Posts)',
                            '8 Blog Articles/Month',
                            'Monthly Performance Dashboard',
                            'Single Dedicated Manager',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => '360° Digital Growth',
                        'value' => '₹19,999',
                        'suffix' => '/mo',
                        'text' => 'Complete digital marketing: SEO + Social + PPC + Content — maximum ROI.',
                        'featured' => true,
                        'badge' => 'Best Seller',
                        'features' => implode("\n", [
                            'SEO (30 Keywords + Link Building)',
                            'Social Media (4 Platforms, 24 Posts + Reels)',
                            'Google Ads (up to ₹50K spend)',
                            '12 Blog Articles + 4 Infographics',
                            'Dedicated Account Manager',
                            'Weekly Reports + Monthly Strategy Call',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                    [
                        'title' => 'Full IT Partner',
                        'value' => '₹39,999',
                        'suffix' => '/mo',
                        'text' => 'Everything — Website + Full Digital Marketing + Branding. Your complete IT partner.',
                        'featured' => false,
                        'features' => implode("\n", [
                            'Full Enterprise SEO',
                            'All Platforms Social Media',
                            'Multi-channel PPC (up to ₹2L spend)',
                            'Website Development + Maintenance',
                            'Full Content Marketing',
                            'Branding & Design Support',
                            'Dedicated Team + CXO Access',
                        ]),
                        'cta_text' => 'Get Started',
                        'url' => '/contact/',
                        'cta_url' => '/contact/',
                    ],
                ],
            ]],
            [self::BLOCK_FEATURE_CARDS, [
                'eyebrow' => 'Customise Your Plan',
                'heading' => 'Optional Add-Ons',
                'subheading' => 'Enhance any package with powerful add-ons tailored to your specific business needs.',
                'background' => 'white',
                'items' => [
                    ['title' => 'App Development', 'icon' => 'smartphone', 'text' => 'Android & iOS apps from ₹49,999'],
                    ['title' => 'AI Chatbot Integration', 'icon' => 'cpu', 'text' => 'Custom chatbot for your website from ₹9,999'],
                    ['title' => 'App Store SEO', 'icon' => 'rocket', 'text' => 'Boost app visibility from ₹4,999/mo'],
                    ['title' => 'Email Marketing', 'icon' => 'mail', 'text' => 'Campaigns + automation from ₹5,999/mo'],
                    ['title' => 'ERP / CRM Setup', 'icon' => 'layers', 'text' => 'Business automation from ₹29,999'],
                    ['title' => 'Website Security & Maintenance', 'icon' => 'shield', 'text' => 'Monthly care from ₹2,999/mo'],
                    ['title' => 'UI/UX Design', 'icon' => 'layout', 'text' => 'Wireframes & prototypes from ₹14,999'],
                    ['title' => 'Video Production', 'icon' => 'video', 'text' => 'Reels, explainer videos from ₹7,999'],
                ],
            ]],
            [self::BLOCK_PROCESS, [
                'eyebrow' => 'How It Works',
                'heading' => 'Get Started in 4 Simple Steps',
                'subheading' => 'From consultation to results — we make onboarding simple and seamless.',
                'background' => 'mist',
                'items' => [
                    ['title' => 'Free Consultation', 'text' => 'Tell us about your business goals. We analyze your current digital presence for free.'],
                    ['title' => 'Choose Your Plan', 'text' => 'Pick a package that fits your budget and business needs. We’ll recommend the best fit.'],
                    ['title' => 'Onboarding & Strategy', 'text' => 'We set up everything and create a custom 90-day roadmap for your growth.'],
                    ['title' => 'Grow & Scale', 'text' => 'Monitor real-time results through your dashboard. Watch your business grow every month.'],
                ],
            ]],
            [self::BLOCK_FEATURE_CARDS, [
                'eyebrow' => 'Why TechAlphonic',
                'heading' => 'Why [[1000+ Clients]] Trust Us',
                'subheading' => 'We’re not just an agency — we’re your long-term digital growth and engineering partner.',
                'background' => 'mist',
                'items' => [
                    ['icon' => 'trending-up', 'title' => 'Proven ROI Results', 'text' => 'Average 3x ROI for our clients within 6 months. We deliver with data-backed, conversion-focused strategies.'],
                    ['icon' => 'user-check', 'title' => 'Dedicated Account Manager', 'text' => 'Single point of contact for your project. No tickets, no delays — direct communication and fast execution.'],
                    ['icon' => 'file-text', 'title' => 'Transparent Reporting', 'text' => "Weekly and monthly reports with clear ranking, traffic, lead, and revenue metrics. You always know what's happening."],
                    ['icon' => 'shield-check', 'title' => 'No Long-Term Lock-in', 'text' => 'Flexible month-to-month plans with zero hidden fees. Cancel anytime with a 30-day notice. We earn your trust every month.'],
                    ['icon' => 'sparkles', 'title' => 'AI-Powered Strategies', 'text' => 'We leverage cutting-edge AI tools for SEO, content creation, and ad optimization to keep you ahead of competitors.'],
                    ['icon' => 'award', 'title' => 'Industry Expertise', 'text' => '10+ years across 20+ industries — healthcare, e-commerce, real estate, finance, education, logistics, and SaaS.'],
                ],
            ]],
            [self::BLOCK_TESTIMONIALS, [
                'eyebrow' => 'Client Testimonials',
                'heading' => 'What Our [[Clients Say]]',
                'subheading' => 'Real feedback from businesses scaling with TechAlphonic.',
                'background' => 'white',
                'limit' => 3,
            ]],
            [self::BLOCK_CTA, [
                'eyebrow' => 'Fast Consultation',
                'heading' => 'Ready to Build Your Dream Project?',
                'subheading' => 'Book a free 30-minute consultation call with our solution architect and get a tailored growth strategy.',
                'cta_text' => 'Book a Free Consultation',
                'cta_url' => '/contact/',
            ]],
        ]);

        $this->page('seo-by-industry', 'SEO by Industry', 'Dominate Google in Your Industry With [[Targeted SEO]]', 'default', [
            'hero_subheading' => 'Industry-Specific SEO Solutions',
            'excerpt' => 'Generic SEO strategies fail because every industry has unique buyer journeys, compliance needs, competitive landscapes, and search behaviors. At TechAlphonic, we build custom SEO strategies crafted specifically for your vertical — delivering qualified traffic that actually converts.',
            'content' => <<<'HTML'
<h2>Why Industry-Focused SEO Outperforms Generic SEO</h2>
<p>A hospital, a hotel, and a logistics firm cannot use the same SEO playbook. Industry-specific search optimization targets the exact queries, platforms, and intent signals that matter in your market.</p>
<ul>
    <li><strong>Deep Industry Knowledge:</strong> We understand your buyers, seasonal trends, industry terminology, and competitive dynamics before writing a single line of strategy.</li>
    <li><strong>Hyper-Targeted Keyword Strategy:</strong> We skip vanity keywords and target the high-intent, industry-specific search terms your actual customers use when ready to buy or book.</li>
    <li><strong>Compliance-Aware SEO Content:</strong> Healthcare, legal, finance — we write content that ranks high and respects industry regulations, E-E-A-T standards, and trust guidelines.</li>
    <li><strong>Measurable Industry ROI:</strong> We track what matters to your industry — appointment bookings, hotel reservations, quote requests, store visits, or B2B leads — not just traffic.</li>
</ul>

<h2>Our Industry SEO Process — How We Rank Your Business</h2>
<p>A proven 5-step process customized to your vertical, market, and growth goals:</p>
<ol>
    <li><strong>01. Industry Analysis:</strong> Deep dive into your vertical’s search landscape, top competitors, seasonal trends, and buyer intent keywords.</li>
    <li><strong>02. Custom SEO Blueprint:</strong> Tailored keyword map, content calendar, technical checklist, and link building strategy built specifically for your industry.</li>
    <li><strong>03. Content &amp; On-Page SEO:</strong> Industry-expert content creation, service page optimization, schema markup, and Google Business Profile enhancement.</li>
    <li><strong>04. Industry Link Building:</strong> High-authority backlinks from industry directories, trade publications, niche blogs, and local business associations.</li>
    <li><strong>05. Track &amp; Scale:</strong> Monthly reporting on rankings, traffic, and industry-specific conversions — continuously refining to dominate your niche.</li>
</ol>

<h2>Why Businesses Trust TechAlphonic for Industry SEO</h2>
<p>Over 10 years of specialized SEO experience across 30+ verticals in India and globally:</p>
<ul>
    <li><strong>Dedicated Industry SEO Specialists:</strong> Your account is managed by an SEO strategist with hands-on experience in your specific vertical — not a generalist handling 30 random accounts.</li>
    <li><strong>Local + National Industry SEO:</strong> Whether you serve one city or operate nationwide, we build geo-targeted and national industry strategies that put you ahead in every market you serve.</li>
    <li><strong>Industry-Specific Reporting:</strong> Monthly reports tailored to your KPIs — calls, bookings, form fills, quote requests, or store visits — with full transparency on every rupee spent.</li>
    <li><strong>Proven Results Across Verticals:</strong> Over 500+ projects ranked on page 1 across healthcare, hospitality, home services, logistics, education, real estate, and e-commerce.</li>
</ul>
HTML,
            'meta_title' => 'SEO by Industry — Dominate Google in Your Industry With Targeted SEO | Tech Alphonic',
            'meta_description' => 'Industry-specific SEO strategies for healthcare, hospitality, real estate, ecommerce, moving companies, pest control, plumbing, logistics and more.',
        ], [
            [self::BLOCK_INDUSTRIES, ['heading' => 'Select your industry to explore our tailored SEO strategy', 'background' => 'white', 'limit' => 20]],
            [self::BLOCK_CTA, [
                'heading' => 'Don’t See Your Industry Listed?',
                'subheading' => 'We work with businesses across 30+ verticals. Talk to our SEO specialists and get a free custom industry SEO audit and strategy within 24 hours.',
                'cta_text' => 'Request a Free Industry SEO Audit',
                'cta_url' => '/contact/',
            ]],
        ]);

        $this->page('privacy-policy', 'Privacy Policy', 'Privacy Policy', 'legal', [
            'hero_subheading' => 'Legal',
            'excerpt' => 'How Tech Alphonic collects, uses, stores, and protects your personal information across our website and services.',
            'content' => <<<'HTML'
<h2>1. Introduction</h2>
<p>Welcome to Tech Alphonic (“Company”, “we”, “our”, “us”). We are committed to protecting your privacy and ensuring that your personal information is handled in a safe and responsible manner. This Privacy Policy outlines how we collect, use, disclose, and safeguard your information when you visit our website <strong>techalphonic.com</strong> or use our services including Web Development, App Development, Digital Marketing, SEO, Social Media Optimization, and graphic design services. By accessing or using our website, you agree to the terms of this Privacy Policy. If you do not agree with the practices described in this policy, please do not use our website or services.</p>

<h2>2. Information We Collect</h2>
<h3>2.1 Personal Information</h3>
<p>We may collect personal information that you voluntarily provide to us when you fill out a contact form, request a quote, subscribe to our newsletter, apply for a job, or communicate with us via phone, email, or WhatsApp. This information may include:</p>
<ul>
    <li>Full Name</li>
    <li>Email Address</li>
    <li>Phone Number / Mobile Number</li>
    <li>Company / Business Name</li>
    <li>Website URL</li>
    <li>Billing and Mailing Address</li>
    <li>Project Requirements and Business Details</li>
    <li>Resume / CV and employment history (for job applicants)</li>
</ul>

<h3>2.2 Automatically Collected Information</h3>
<p>When you visit our website, certain information is collected automatically through cookies and similar tracking technologies, including:</p>
<ul>
    <li>IP Address and approximate geographic location</li>
    <li>Browser type and version</li>
    <li>Operating system and device information</li>
    <li>Pages visited, time spent on pages, and clickstream data</li>
    <li>Referring website or search terms that led you to our site</li>
</ul>

<h2>3. How We Use Your Information</h2>
<p>We use the information we collect for the following legitimate business purposes:</p>
<ul>
    <li>To respond to your inquiries, consultation requests, and customer service questions</li>
    <li>To prepare proposals, quotes, and service agreements tailored to your business requirements</li>
    <li>To deliver, manage, and improve our web development, app development, SEO, and digital marketing services</li>
    <li>To process payments, invoices, and billing transactions</li>
    <li>To send administrative updates, project reports, and service notifications</li>
    <li>To send marketing communications, newsletters, and promotional offers (which you may opt out of at any time)</li>
    <li>To analyze website usage, monitor performance, and improve user experience</li>
    <li>To comply with applicable laws, tax obligations, and regulatory requirements</li>
</ul>

<h2>4. Cookies and Tracking Technologies</h2>
<p>Our website uses cookies, pixels, and analytics tools (such as Google Analytics, Google Search Console, and Meta Pixel) to understand how visitors interact with our website and to improve our marketing relevance. You can choose to disable or selectively turn off cookies in your browser settings. However, disabling cookies may affect how you are able to interact with certain features of our website.</p>

<h2>5. How We Share Your Information</h2>
<p>We do <strong>not</strong> sell, trade, or rent your personal information to third parties. We may share your information only in the following limited circumstances:</p>
<ul>
    <li><strong>Trusted Service Providers:</strong> With third-party vendors who assist us in operating our website, hosting, analytics, payment processing, email delivery, or project tools, subject to strict confidentiality obligations.</li>
    <li><strong>Legal Compliance:</strong> When required by law, subpoena, court order, or government regulation, or when we believe in good faith that disclosure is necessary to protect our rights, safety, or property.</li>
    <li><strong>Business Transfers:</strong> In connection with any merger, acquisition, restructuring, or sale of company assets.</li>
</ul>

<h2>6. Data Security</h2>
<p>We implement appropriate technical, administrative, and physical security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. While we strive to use commercially acceptable means to protect your personal data, please note that no method of transmission over the Internet or electronic storage is 100% secure.</p>

<h2>7. Data Retention</h2>
<p>We retain your personal information only for as long as necessary to fulfill the purposes for which it was collected, including providing our services, maintaining business records, resolving disputes, and complying with legal, accounting, or reporting requirements.</p>

<h2>8. Third-Party Links</h2>
<p>Our website may contain links to external third-party websites, plugins, or platforms. We have no control over and assume no responsibility for the content, privacy policies, or practices of any third-party sites or services. We encourage you to review the privacy policy of every website you visit.</p>

<h2>9. Your Rights and Choices</h2>
<p>Depending on your location and applicable data protection laws, you may have the right to:</p>
<ul>
    <li>Access and receive a copy of the personal data we hold about you</li>
    <li>Request correction or updating of inaccurate or incomplete personal information</li>
    <li>Request deletion of your personal data, subject to legal or contractual retention obligations</li>
    <li>Withdraw consent or opt out of receiving marketing communications at any time</li>
</ul>
<p>To exercise any of these rights, please contact us at <a href="mailto:info@techalphonic.com">info@techalphonic.com</a>.</p>

<h2>10. Children’s Privacy</h2>
<p>Our website and services are intended for business professionals and individuals aged 18 and above. We do not knowingly collect personal information from children under the age of 18. If we become aware that we have inadvertently collected personal data from a minor, we will take steps to delete such information promptly.</p>

<h2>11. Changes to This Privacy Policy</h2>
<p>Tech Alphonic reserves the right to update or modify this Privacy Policy at any time. Any changes will be posted on this page with an updated revision date. We encourage you to review this Privacy Policy periodically to stay informed about how we are protecting your information.</p>

<h2>12. Contact Us</h2>
<p>If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
<ul>
    <li><strong>Company:</strong> Tech Alphonic Pvt. Ltd.</li>
    <li><strong>Address:</strong> C-203, C Block, Sector 63, Hazratpur Wajidpur, Noida, Uttar Pradesh 201309, India</li>
    <li><strong>Phone:</strong> <a href="tel:+919899184918">+91 98991 84918</a></li>
    <li><strong>Email:</strong> <a href="mailto:info@techalphonic.com">info@techalphonic.com</a></li>
</ul>
HTML,
            'robots_index' => true,
        ]);

        $this->page('terms-condition', 'Terms & Conditions', 'Terms & Conditions', 'legal', [
            'hero_subheading' => 'Legal',
            'excerpt' => 'Terms and conditions governing your use of Tech Alphonic’s website and digital marketing, SEO, web, and app development services.',
            'content' => <<<'HTML'
<h2>1. Introduction</h2>
<p>Welcome to Tech Alphonic (“Company”, “we”, “our”, “us”). These Terms and Conditions (“Terms”) govern your access to and use of our website <strong>techalphonic.com</strong> and all services provided by Tech Alphonic, including Website Development, Mobile App Development, Custom Software Development, Search Engine Optimization (SEO), Social Media Marketing (SMM), Pay-Per-Click (PPC) Advertising, Content Marketing, and Branding Services. By accessing our website or engaging our services, you agree to be bound by these Terms.</p>

<h2>2. Scope of Services</h2>
<p>Tech Alphonic provides digital marketing, web design, web development, mobile application development, and related IT consulting services. The specific deliverables, timelines, milestones, and fees for any project or retainer will be set out in a written Proposal, Statement of Work (SOW), or Invoice agreed upon between Tech Alphonic and the Client.</p>

<h2>3. Client Responsibilities</h2>
<p>To ensure timely and successful delivery of services, the Client agrees to:</p>
<ul>
    <li>Provide accurate and complete project requirements, brand assets, content, credentials, and approvals in a timely manner.</li>
    <li>Designate a primary point of contact authorized to review deliverables and provide approvals.</li>
    <li>Ensure that all text, images, trademarks, data, and materials supplied to Tech Alphonic do not infringe any third-party intellectual property rights or violate any applicable laws.</li>
</ul>

<h2>4. Quotations, Fees, and Payment Terms</h2>
<ul>
    <li>All project estimates and quotes are valid for 30 days from the date of issue unless stated otherwise.</li>
    <li>For development and design projects, an advance payment (typically 50% of the total project fee, or as specified in the proposal) is required before work commences, with remaining milestones payable as agreed prior to final deployment.</li>
    <li>For monthly recurring services (such as SEO, Social Media Marketing, PPC Management, and Content Marketing), fees are billed in advance on a monthly cycle.</li>
    <li>Third-party costs — including domain registration, web hosting, paid plugins/themes, stock media licenses, App Store / Play Store developer fees, and paid advertising spend (Google Ads, Meta Ads, LinkedIn Ads, etc.) — are borne directly by the Client unless explicitly included in the written proposal.</li>
    <li>All fees are exclusive of applicable GST or statutory taxes unless explicitly stated otherwise.</li>
</ul>

<h2>5. Revisions and Scope Changes</h2>
<p>Each package or proposal includes a defined number of design or content revisions. Requests that fall outside the agreed scope of work, or additional revisions beyond the included rounds, will be treated as a change request and billed separately at our standard hourly or mutually agreed rate after Client approval.</p>

<h2>6. Intellectual Property Rights</h2>
<ul>
    <li>Upon receipt of full and final payment for a project, the Client is granted ownership or a perpetual license to the final approved website design, custom code, graphics, or written content created specifically for the Client under that engagement.</li>
    <li>Tech Alphonic retains ownership of its pre-existing tools, reusable code libraries, internal frameworks, templates, and know-how used in delivering the services.</li>
    <li>Unless otherwise agreed in writing, Tech Alphonic reserves the right to showcase completed work, rankings achieved, and the Client’s name/logo in its portfolio and marketing materials.</li>
</ul>

<h2>7. Search Engine Optimization (SEO) &amp; Digital Marketing Disclaimer</h2>
<ul>
    <li>Tech Alphonic follows ethical, white-hat SEO and digital marketing practices aligned with search engine and platform guidelines.</li>
    <li>Because search engine algorithms (such as Google, Bing, and AI search engines) and third-party ad platforms change frequently and are outside any agency’s control, Tech Alphonic does not guarantee specific #1 rankings, fixed traffic numbers, or exact sales volumes.</li>
    <li>We will not be responsible for ranking drops or account disruptions caused by changes made to the website or ad accounts by the Client or third parties without our prior consultation.</li>
</ul>

<h2>8. Confidentiality</h2>
<p>Both parties agree to keep confidential all non-public business, technical, financial, and customer information disclosed during the course of an engagement and not to disclose such information to any third party except as required to perform the services or by law.</p>

<h2>9. Warranties and Post-Launch Support</h2>
<p>For web and application development projects, Tech Alphonic provides a post-launch support window (as specified in the package or proposal) to fix any verifiable bugs in the delivered scope. This warranty does not cover issues caused by third-party hosting outages, plugin updates, browser changes, or modifications made by the Client or external developers after handover.</p>

<h2>10. Limitation of Liability</h2>
<p>To the maximum extent permitted by applicable law, Tech Alphonic shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including loss of profits, revenue, data, or business opportunities arising out of or related to the use of our website or services. In all cases, Tech Alphonic’s total aggregate liability under any engagement shall not exceed the total fees actually paid by the Client to Tech Alphonic for the specific service during the three (3) months immediately preceding the claim.</p>

<h2>11. Term, Cancellation, and Termination</h2>
<ul>
    <li>Monthly retainer services operate on a month-to-month basis unless a specific term is agreed in writing. Either party may cancel a monthly retainer by providing at least 15 days’ written notice prior to the next billing cycle.</li>
    <li>If a development project is cancelled by the Client mid-way, payments made for completed milestones and work already performed are non-refundable.</li>
    <li>Tech Alphonic may suspend or terminate services if invoices remain unpaid beyond the due date despite written reminders, or if the Client breaches these Terms.</li>
</ul>

<h2>12. Governing Law and Jurisdiction</h2>
<p>These Terms and any dispute arising out of or in connection with our website or services shall be governed by and construed in accordance with the laws of India. The courts located in Gautam Buddha Nagar (Noida), Uttar Pradesh, India shall have exclusive jurisdiction.</p>

<h2>13. Changes to These Terms</h2>
<p>We reserve the right to update or revise these Terms &amp; Conditions at any time. Updated Terms will be posted on this page and take effect immediately upon publication.</p>

<h2>14. Contact Information</h2>
<p>For any questions regarding these Terms &amp; Conditions, please contact:</p>
<ul>
    <li><strong>Company:</strong> Tech Alphonic Pvt. Ltd.</li>
    <li><strong>Address:</strong> C-203, C Block, Sector 63, Hazratpur Wajidpur, Noida, Uttar Pradesh 201309, India</li>
    <li><strong>Phone:</strong> <a href="tel:+919899184918">+91 98991 84918</a></li>
    <li><strong>Email:</strong> <a href="mailto:info@techalphonic.com">info@techalphonic.com</a></li>
</ul>
HTML,
            'robots_index' => true,
        ]);
    }

    private function page(string $slug, string $title, string $h1, string $template, array $data, array $blocks = []): Page
    {
        $page = Page::updateOrCreate(['slug' => $slug], [
            'title' => $title,
            'h1' => $h1,
            'hero_subheading' => $data['hero_subheading'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'] ?? null,
            'template' => $template,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $page->syncSeo([
            'meta_title' => $data['meta_title'] ?? $title.' | Tech Alphonic',
            'meta_description' => $data['meta_description'] ?? $data['excerpt'] ?? null,
            'robots_index' => $data['robots_index'] ?? true,
            'robots_follow' => true,
            'include_in_sitemap' => $data['robots_index'] ?? true,
        ]);

        $keepIds = [];
        foreach ($blocks as $i => [$type, $blockData]) {
            $block = $page->blocks()->updateOrCreate(
                ['sort_order' => $i],
                ['type' => $type, 'data' => $blockData, 'is_visible' => true]
            );
            $keepIds[] = $block->id;
        }
        if ($blocks !== []) {
            $page->blocks()->whereNotIn('id', $keepIds)->delete();
        }

        return $page;
    }
}
