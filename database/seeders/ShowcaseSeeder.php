<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\Media;
use App\Models\Portfolio;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Real case study published on the old website (Our Work page): keyword rankings for an Ayurveda clinic-software
 * client. Rankings are copied exactly as published there. Portfolio items are added in Admin → Portfolio once
 * project screenshots are available (the old site only had template placeholders).
 */
class ShowcaseSeeder extends Seeder
{
    private const KEYWORDS = [
        ['Ayurveda clinical management software', '#1'],
        ['Clinical management software for Ayurveda', '#2'],
        ['Best software for Ayurveda doctors', '#1'],
        ['Ayurveda patient record system', '#2'],
        ['Doctor management software Ayurveda', '#2'],
        ['Ayurveda doctor appointment management', '#2'],
        ['Healthcare software for Ayurveda clinics', '#1'],
        ['Ayurveda practice management software', '#2'],
    ];

    public function run(): void
    {
        // Remove the starter demo items.
        CaseStudy::withTrashed()->where('slug', 'like', 'demo-%')->get()->each->forceDelete();
        Portfolio::withTrashed()->where('slug', 'like', 'demo-%')->get()->each->forceDelete();

        $rows = collect(self::KEYWORDS)->map(fn ($k, $i) => '<tr><td>'.($i + 1).'</td><td>'.e($k[0]).'</td><td>Not in top 100</td><td><strong>'.$k[1].'</strong></td></tr>')->implode('');
        $first = collect(self::KEYWORDS)->where(1, '#1')->count();

        CaseStudy::updateOrCreate(['slug' => 'ayurveda-clinic-software-seo'], [
            'title' => 'Ayurveda clinic software: from outside the top 100 to #1 on Google',
            'client' => 'Ayurveda healthcare software company',
            'industry_id' => Industry::where('slug', 'healthcare')->value('id'),
            'service_id' => Service::where('slug', 'search-engine-optimization')->value('id'),
            'summary' => 'Every target keyword for an Ayurveda clinic-management product moved from outside the top 100 to position #1 or #2 on Google.',
            'challenge' => '<p>The client sells clinic-management software built for Ayurveda practitioners. Its website did not rank in the top 100 results for any of the searches its buyers use — such as “Ayurveda clinical management software” or “Ayurveda patient record system”.</p>',
            'solution' => '<p>An SEO programme built around the high-intent searches Ayurveda doctors use when looking for practice software, covering the product and feature pages of the website.</p>',
            'results' => '<p>All eight target keywords now rank on the first two positions of Google.</p><table><thead><tr><th>#</th><th>Keyword</th><th>Before</th><th>Now</th></tr></thead><tbody>'.$rows.'</tbody></table>',
            'technologies' => ['SEO'],
            'metrics' => [
                ['value' => '8/8', 'label' => 'Target keywords in the top 2'],
                ['value' => (string) $first, 'label' => 'Keywords ranking #1'],
                ['value' => '#1–#2', 'label' => 'Positions now held, from outside the top 100'],
            ],
            'featured_image_id' => Media::where('original_name', 'ayurgrid.jpg')->value('id'),
            'status' => ContentStatus::Published, 'published_at' => now(), 'sort_order' => 0,
        ])->syncSeo([
            'meta_title' => 'Ayurveda Software SEO Case Study — #1 Rankings | Tech Alphonic',
            'meta_description' => 'How an Ayurveda clinic-management software went from outside the top 100 to #1 and #2 on Google for all 8 target keywords.',
            'robots_index' => true, 'include_in_sitemap' => true,
        ]);
    }
}
