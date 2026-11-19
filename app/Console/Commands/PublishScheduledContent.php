<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Models\Blog;
use App\Models\CaseStudy;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishScheduledContent extends Command
{
    protected $signature = 'content:publish-scheduled';

    protected $description = 'Publish content whose scheduled publish date has passed';

    public function handle(): int
    {
        $total = 0;
        foreach ([Page::class, Service::class, ServiceCategory::class, LandingPage::class, Blog::class, Portfolio::class, CaseStudy::class] as $class) {
            $class::where('status', ContentStatus::Scheduled->value)
                ->where('published_at', '<=', now())
                ->each(function ($item) use (&$total) {
                    $item->status = ContentStatus::Published;
                    $item->save(); // fires model events → caches + sitemap refresh
                    $total++;
                });
        }

        if ($total) {
            Log::info("Published $total scheduled item(s).");
        }
        $this->info("Published $total item(s).");

        return self::SUCCESS;
    }
}
