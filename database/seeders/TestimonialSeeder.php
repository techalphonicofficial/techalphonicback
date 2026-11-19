<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Reviews shown on the old website's Packages page, imported as INACTIVE drafts.
 * They could not be verified (one reviewer of "TechCorp Solutions" talks about "my clinic"), so they stay hidden
 * until the business confirms each one is a genuine, permission-granted client review — publishing invented
 * reviews breaks consumer-protection rules and Google's review policy. Enable them in Admin → Testimonials.
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        Testimonial::where('name', 'like', 'Demo Client%')->delete();

        $seo = Service::where('slug', 'search-engine-optimization')->value('id');
        $web = Service::where('slug', 'website-development')->value('id');

        $rows = [
            ['Suresh Mehta', 'Owner', 'Mehta Legal Associates', 'Tech Alphonic took our website from page 5 to page 1 for our main keyword in just 4 months! Our organic leads have tripled. The team is responsive and the reports are crystal clear.', $seo],
            ['Neha Kapoor', 'Founder', 'NKart Fashion Store', 'We chose the Combo Growth package and within 3 months our online store revenue went up by 140%. Google Ads + SEO combo is phenomenal. Highly recommend their team!', $seo],
            ['Amit Gupta', 'Founder', 'TechCorp Solutions', 'Their web development + SEO package was exactly what my clinic needed. Patients are now finding us on Google. The website looks professional and loads super fast!', $web],
        ];

        foreach ($rows as $i => [$name, $designation, $company, $review, $serviceId]) {
            Testimonial::firstOrCreate(['name' => $name], [
                'designation' => $designation, 'company' => $company, 'review' => $review, 'rating' => 5,
                'service_id' => $serviceId, 'is_active' => false, 'sort_order' => $i,
            ]);
        }
    }
}
