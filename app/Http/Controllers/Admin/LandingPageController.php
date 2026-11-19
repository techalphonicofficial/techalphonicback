<?php

namespace App\Http\Controllers\Admin;

use App\Ai\AiException;
use App\Ai\Pseo\PseoContentWriter;
use App\Enums\LandingPageType;
use App\Http\Requests\Admin\LandingPageRequest;
use App\Models\ActivityLog;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Location;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Services\LandingPageGenerator;
use App\Services\TemplateRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LandingPageController extends ContentController
{
    protected string $modelClass = LandingPage::class;

    protected string $resource = 'landing_pages';

    protected string $routeName = 'admin.landing-pages';

    protected string $viewPath = 'admin.landing-pages';

    protected string $label = 'SEO page';

    protected string $labelPlural = 'Programmatic SEO Pages';

    protected array $searchColumns = ['title', 'slug', 'h1'];

    protected array $sortable = ['title', 'published_at'];

    protected array $listWith = ['service:id,name', 'location:id,name', 'industry:id,name', 'latestAiGeneration'];

    public function __construct(private LandingPageGenerator $generator, private PseoContentWriter $writer) {}

    protected function requestClass(): string
    {
        return LandingPageRequest::class;
    }

    protected function applyFilters($query, Request $request): void
    {
        foreach (['page_type', 'service_id', 'location_id', 'industry_id'] as $f) {
            if ($v = $request->query($f)) {
                $query->where($f, $v);
            }
        }
        if ($request->query('index') === 'noindex') {
            $query->whereHas('seo', fn ($q) => $q->where('robots_index', false));
        }
    }

    protected function filterOptions(): array
    {
        return [
            'page_type' => ['label' => 'Type', 'options' => LandingPageType::options()],
            'service_id' => ['label' => 'Service', 'options' => Service::orderBy('name')->pluck('name', 'id')->all()],
            'location_id' => ['label' => 'Location', 'options' => Location::orderBy('name')->pluck('name', 'id')->all()],
            'industry_id' => ['label' => 'Industry', 'options' => Industry::orderBy('name')->pluck('name', 'id')->all()],
            'index' => ['label' => 'Indexing', 'options' => ['noindex' => 'Noindex only']],
        ];
    }

    protected function formData(Model $item): array
    {
        return [
            'services' => Service::orderBy('name')->pluck('name', 'id'),
            'locations' => Location::orderBy('name')->get(['id', 'name', 'state']),
            'industries' => Industry::orderBy('name')->pluck('name', 'id'),
            'variables' => TemplateRenderer::VARIABLES,
        ];
    }

    /** Step 1 of the generator: choose type + entities. Step 2 is the normal form, pre-filled. */
    public function create()
    {
        $this->authorizePermission('create');
        $request = request();

        if ($request->filled(['page_type', 'service_id'])) {
            $type = LandingPageType::from($request->query('page_type'));
            $service = Service::findOrFail($request->integer('service_id'));
            $location = $type->needsLocation() ? Location::findOrFail($request->integer('location_id')) : null;
            $industry = $type->needsIndustry() ? Industry::findOrFail($request->integer('industry_id')) : null;

            if ($this->generator->exists($service->id, $location?->id, $industry?->id)) {
                return back()->with('error', 'A page for this combination already exists.');
            }

            $draft = $this->generator->draft($type, $service, $location, $industry);
            $seo = $draft['seo'];
            unset($draft['seo']);
            $item = new LandingPage($draft);
            $item->setRelation('seo', new SeoMeta($seo));
            $item->setRelation('service', $service);
            $item->setRelation('location', $location);
            $item->setRelation('industry', $industry);

            return $this->form($item);
        }

        return view('admin.landing-pages.generator', $this->formData(new LandingPage) + ['types' => LandingPageType::options()]);
    }

    public function bulkForm()
    {
        abort_unless(request()->user()->can('seo.bulk'), 403);

        return view('admin.landing-pages.bulk', $this->formData(new LandingPage) + ['types' => LandingPageType::options()]);
    }

    /** Bulk generation — always creates drafts with noindex; nothing goes live automatically. */
    public function bulkGenerate(Request $request)
    {
        abort_unless($request->user()->can('seo.bulk') && $request->user()->can('landing_pages.create'), 403);

        $data = $request->validate([
            'page_type' => ['required', Rule::enum(LandingPageType::class)],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'location_ids' => ['array', 'max:200'],
            'location_ids.*' => ['integer', 'exists:locations,id'],
            'industry_ids' => ['array', 'max:50'],
            'industry_ids.*' => ['integer', 'exists:industries,id'],
        ]);
        $type = LandingPageType::from($data['page_type']);

        if (($type->needsLocation() && empty($data['location_ids'])) || ($type->needsIndustry() && empty($data['industry_ids']))) {
            return back()->withInput()->with('error', 'Select at least one location / industry for this page type.');
        }

        $result = $this->generator->bulk($type, Service::findOrFail($data['service_id']), $data['location_ids'] ?? [], $data['industry_ids'] ?? []);
        ActivityLog::record('bulk_generated', null, "Generated {$result['created']} draft SEO pages", ['created' => $result['created'], 'skipped' => $result['skipped'], 'type' => $type->value]);
        $message = "{$result['created']} draft pages created, {$result['skipped']} skipped (already exist).";

        if ($request->boolean('ai_write') && $request->user()->can('ai.generate') && $result['ids']) {
            $ai = $this->writer->queueMany(LandingPage::whereIn('id', $result['ids'])->get());
            $message .= $ai['queued'] ? " AI is writing {$ai['queued']} of them in the background; check the AI writing log for progress." : ' AI writing was not started: '.collect($ai['skipped'])->first();

            return redirect()->route('admin.ai.generations', ['batch' => $ai['batch']])->with($ai['queued'] ? 'success' : 'warning', $message);
        }

        return redirect()->route('admin.landing-pages.index', ['status' => 'draft', 'service_id' => $data['service_id']])
            ->with('success', $message.' Review each page, add unique content, then publish.');
    }

    /** Bulk SEO editor: inline-edit meta for many pages at once. */
    public function bulkSeo(Request $request)
    {
        abort_unless($request->user()->can('seo.bulk'), 403);
        $query = LandingPage::query()->with(['seo', 'service:id,name', 'location:id,name', 'industry:id,name']);
        $this->applyFilters($query, $request);
        if ($s = $request->query('status')) {
            $query->where('status', $s);
        }

        return view('admin.landing-pages.bulk-seo', [
            'items' => $query->orderBy('title')->paginate(50)->withQueryString(),
            'filters' => $this->filterOptions(),
        ]);
    }

    public function bulkSeoUpdate(Request $request)
    {
        abort_unless($request->user()->can('seo.bulk'), 403);
        $data = $request->validate([
            'rows' => ['required', 'array', 'max:100'],
            'rows.*.meta_title' => ['nullable', 'string', 'max:255'],
            'rows.*.meta_description' => ['nullable', 'string', 'max:500'],
            'rows.*.focus_keyword' => ['nullable', 'string', 'max:255'],
            'rows.*.canonical_url' => ['nullable', 'string', 'max:500', 'regex:#^(https?://|/)#'],
            'rows.*.robots_index' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            $pages = LandingPage::whereIn('id', array_keys($data['rows']))->get()->keyBy('id');
            foreach ($data['rows'] as $id => $row) {
                if ($page = $pages[$id] ?? null) {
                    $row['robots_index'] = (bool) ($row['robots_index'] ?? false);
                    $row['include_in_sitemap'] = $row['robots_index'];
                    $page->syncSeo($row);
                }
            }
        });
        ActivityLog::record('bulk_seo_updated', null, 'Bulk SEO update', ['count' => count($data['rows'])]);

        return back()->with('success', count($data['rows']).' pages updated.');
    }

    /** Bulk actions from the index checkboxes. */
    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
            'bulk_action' => ['required', Rule::in(['publish', 'unpublish', 'noindex', 'index', 'delete', 'ai_write'])],
        ]);
        $action = $data['bulk_action'];
        $permission = match ($action) {
            'publish', 'unpublish' => 'landing_pages.publish',
            'delete' => 'landing_pages.delete',
            'ai_write' => 'ai.generate',
            default => 'seo.edit',
        };
        abort_unless($request->user()->can($permission), 403);

        $pages = LandingPage::whereIn('id', $data['ids'])->get();
        if ($action === 'ai_write') {
            $ai = $this->writer->queueMany($pages);
            ActivityLog::record('ai_queued', null, "Queued AI writing for {$ai['queued']} SEO pages", ['batch' => $ai['batch'], 'skipped' => count($ai['skipped'])]);
            if (! $ai['queued']) {
                return back()->with('error', 'AI writing was not started: '.collect($ai['skipped'])->first());
            }

            return redirect()->route('admin.ai.generations', ['batch' => $ai['batch']])->with('success', "AI is writing {$ai['queued']} pages.".($ai['skipped'] ? ' '.count($ai['skipped']).' skipped (live pages or already in progress).' : ''));
        }
        DB::transaction(function () use ($pages, $action) {
            foreach ($pages as $page) {
                match ($action) {
                    'publish' => $page->publish(),
                    'unpublish' => $page->unpublish(),
                    'noindex' => $page->syncSeo(['robots_index' => false, 'include_in_sitemap' => false]),
                    'index' => $page->syncSeo(['robots_index' => true, 'include_in_sitemap' => true]),
                    'delete' => $page->delete(),
                };
            }
        });
        ActivityLog::record('bulk_'.$action, null, "Bulk $action on {$pages->count()} SEO pages", ['ids' => $data['ids']]);

        return back()->with('success', ucfirst($action).' applied to '.$pages->count().' pages.');
    }

    /** "Write with AI" on the edit screen: content is applied straight to the draft by a queued job. */
    public function aiGenerate(Request $request, int $id)
    {
        abort_unless($request->user()->can('ai.generate'), 403);
        $page = $this->find($id);
        $this->authorizeItem('update', $page);

        try {
            $this->writer->queue($page);
        } catch (AiException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'AI is writing this page. It updates automatically when the draft is ready.');
    }

    public function aiStatus(int $id)
    {
        abort_unless(request()->user()->can('landing_pages.view'), 403);
        $g = LandingPage::withTrashed()->findOrFail($id)->latestAiGeneration;

        return response()->json($g ? [
            'id' => $g->id,
            'status' => $g->status,
            'label' => $g->statusLabel(),
            'pending' => $g->isPending(),
            'error' => $g->error,
            'waiting_seconds' => $g->status === 'queued' ? (int) $g->created_at->diffInSeconds(now()) : 0,
        ] : ['status' => null, 'pending' => false]);
    }

    /** New pages can be handed to AI straight from the create form. */
    protected function afterSave(Model $item, FormRequest $request): void
    {
        if ($item->wasRecentlyCreated && $request->boolean('ai_generate') && $request->user()->can('ai.generate')) {
            try {
                $this->writer->queue($item); // progress shows in the AI card on the edit screen
            } catch (AiException $e) {
                session()->flash('warning', 'Page created, but AI writing was not started: '.$e->getMessage());
            }
        }
    }

    public function index(Request $request)
    {
        $this->authorizePermission('view');
        $response = parent::index($request);
        $counts = LandingPage::selectRaw('page_type, status, count(*) as c')->groupBy('page_type', 'status')->get();

        return $response->with('counts', $counts);
    }

    protected function indexView(): string
    {
        return 'admin.landing-pages.index';
    }

    protected function defaults(): array
    {
        return parent::defaults() + ['hero_layout' => 'split', 'faq_enabled' => true, 'show_testimonials' => true, 'show_industries' => true];
    }
}
