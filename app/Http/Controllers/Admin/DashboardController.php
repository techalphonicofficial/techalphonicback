<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Faq;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\Location;
use App\Models\Media;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Service;
use App\Services\SeoReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SeoReportService $report)
    {
        abort_unless($request->user()->can('dashboard.view'), 403);

        $stats = Cache::remember('admin.dashboard.stats', 120, function () {
            $published = ContentStatus::Published->value;
            $pageCount = Page::count() + LandingPage::count();

            return [
                'Total pages' => $pageCount,
                'Published pages' => Page::where('status', $published)->count() + LandingPage::where('status', $published)->count(),
                'Draft pages' => Page::where('status', 'draft')->count() + LandingPage::where('status', 'draft')->count(),
                'Services' => Service::count(),
                'SEO pages' => LandingPage::count(),
                'Locations' => Location::count(),
                'Industries' => Industry::count(),
                'Blogs' => Blog::count(),
                'Leads' => Lead::count(),
                'FAQs' => Faq::count(),
                'Media' => Media::count(),
                'Redirects' => Redirect::count(),
            ];
        });

        $leadQuery = Lead::query()->with('assignee:id,name')->latest();
        if (! $request->user()->can('leads.view_all')) {
            $leadQuery->where(fn ($q) => $q->whereNull('assigned_to')->orWhere('assigned_to', $request->user()->id));
        }

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentLeads' => $request->user()->can('leads.view') ? $leadQuery->limit(8)->get() : collect(),
            'newLeads' => Lead::where('status', LeadStatus::New->value)->count(),
            'leadsByDay' => Lead::where('created_at', '>=', now()->subDays(13)->startOfDay())
                ->selectRaw('DATE(created_at) as d, count(*) as c')->groupBy('d')->pluck('c', 'd'),
            // Previous 14-day window, for the trend on the leads KPI.
            'leadsPrev' => Lead::whereBetween('created_at', [now()->subDays(27)->startOfDay(), now()->subDays(14)->endOfDay()])->count(),
            'seoReport' => $request->user()->can('seo.edit') ? $report->summary() : null,
        ]);
    }
}
