<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Author;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $cats = collect([
            'seo' => ['SEO', 'Search engine optimisation guides, checklists and strategy.'],
            'digital-marketing' => ['Digital Marketing', 'Performance marketing, social media and content.'],
            'web-development' => ['Web Development', 'Websites, Laravel, WordPress and app development.'],
        ])->map(fn ($v, $slug) => BlogCategory::updateOrCreate(['slug' => $slug], ['name' => $v[0], 'description' => $v[1]]));

        $author = Author::first();
        $blogFaqCat = FaqCategory::updateOrCreate(['slug' => 'blog'], ['name' => 'Blog', 'sort_order' => 9]);

        $posts = [
            ['local-seo-checklist-for-small-businesses', 'Local SEO Checklist for Small Businesses (2026 Edition)', 'seo', ['Local SEO', 'Google Business Profile'], 'local-seo',
                'A practical, step-by-step local SEO checklist to help small businesses appear in Google’s map pack and “near me” searches.',
                '<p>Local SEO helps customers nearby find your business at the exact moment they need you. This checklist covers the essentials in the order we tackle them for clients.</p>
<h2>1. Claim and complete your Google Business Profile</h2><p>Choose accurate primary and secondary categories, add services, opening hours, photos and a clear description. Keep your name, address and phone number consistent everywhere.</p>
<h3>Post updates regularly</h3><p>Weekly posts about offers, projects and news signal an active business and give customers reasons to choose you.</p>
<h2>2. Build consistent citations</h2><p>List your business on reputable directories and industry sites with identical NAP details.</p>
<h2>3. Create location-relevant pages</h2><p>Each area you serve deserves helpful, unique content — not a copy-paste page with the city name swapped.</p>
<h3>Add local proof</h3><p>Include projects, testimonials and photos from the area.</p>
<h2>4. Earn and respond to reviews</h2><p>Ask happy customers for reviews and reply to every review — positive or negative — professionally.</p>
<h2>5. Track calls and enquiries</h2><p>Measure phone clicks, WhatsApp chats and form submissions so you know which activities generate business.</p>',
                [['Does local SEO work without a physical office?', 'Service-area businesses can rank locally by setting a service area on Google Business Profile and building genuinely useful pages for the areas they serve.'],
                    ['How many reviews do I need?', 'There is no magic number — aim for a steady flow of recent, genuine reviews and respond to all of them.']]],
            ['how-to-choose-a-web-development-company', 'How to Choose a Web Development Company: 10 Questions to Ask', 'web-development', ['Website Development', 'Hiring'], 'website-development',
                'Avoid costly rebuilds — use these ten questions to evaluate a web development partner before you sign.',
                '<p>Your website is a long-term asset. Choosing the right partner saves money, time and frustration.</p>
<h2>Questions about process</h2><h3>How do you gather requirements?</h3><p>Good teams run discovery workshops and document scope before quoting.</p><h3>Will I see progress during the build?</h3><p>Look for staging previews and regular demos.</p>
<h2>Questions about quality</h2><h3>How do you handle performance and SEO?</h3><p>Ask about Core Web Vitals, clean URLs, schema and image optimisation.</p><h3>How is security handled?</h3><p>Expect answers on updates, backups, validation and access control.</p>
<h2>Questions about ownership</h2><h3>Who owns the code and content?</h3><p>You should — get it in writing.</p><h3>What happens after launch?</h3><p>Clarify support, maintenance and response times.</p>',
                [['How long does a website project take?', 'A typical business website takes 3–6 weeks; custom applications vary with scope.']]],
            ['programmatic-seo-without-thin-content', 'Programmatic SEO Without Thin Content: A Practical Guide', 'seo', ['Programmatic SEO', 'Content'], 'search-engine-optimization',
                'How to scale location and industry pages that actually help users — and avoid doorway-page penalties.',
                '<p>Programmatic SEO lets you target hundreds of long-tail searches, but only if each page offers real value.</p>
<h2>Start with genuine demand</h2><p>Validate that people actually search for each combination before you create a page.</p>
<h2>Make every page unique</h2><h3>Use real local or industry data</h3><p>Add facts, examples, FAQs and case studies specific to that page.</p><h3>Vary layouts and sections</h3><p>Identical templates with swapped city names are a red flag.</p>
<h2>Publish gradually</h2><p>Launch drafts, review quality, then publish in batches while monitoring indexing in Search Console.</p>
<h2>Control indexing</h2><p>Keep weak pages noindexed until they are improved, and consolidate overlapping pages with canonicals or redirects.</p>',
                [['Is programmatic SEO against Google’s guidelines?', 'No — but mass-produced pages with little unique value can be treated as doorway pages. Quality and usefulness are what matter.']]],
        ];

        foreach ($posts as $i => [$slug, $title, $cat, $tags, $serviceSlug, $excerpt, $content, $faqs]) {
            $blog = Blog::updateOrCreate(['slug' => $slug], [
                'title' => $title, 'blog_category_id' => $cats[$cat]->id, 'author_id' => $author?->id,
                'excerpt' => $excerpt, 'content' => $content, 'show_toc' => true, 'faq_enabled' => true,
                'status' => ContentStatus::Published, 'published_at' => now()->subDays(($i + 1) * 9),
            ]);
            $blog->tags()->sync(collect($tags)->map(fn ($t) => BlogTag::firstOrCreate(['slug' => Str::slug($t)], ['name' => $t])->id));
            $blog->services()->sync(Service::where('slug', $serviceSlug)->pluck('id'));
            $blog->syncSeo(['meta_title' => $title, 'meta_description' => $excerpt, 'focus_keyword' => strtolower($tags[0])]);
            $blog->syncFaqs(collect($faqs)->map(fn ($f, $k) => Faq::updateOrCreate(['question' => $f[0]], ['faq_category_id' => $blogFaqCat->id, 'answer' => '<p>'.$f[1].'</p>', 'is_active' => true, 'sort_order' => $k])->id)->all());
        }
    }
}
