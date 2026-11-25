<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Faq;
use App\Models\UrlSlug;
use App\Services\SeoAuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Shared CRUD + publishing workflow for CMS content types. Concrete controllers declare the model,
 * permission resource, form request and any extra form data — they stay small and specific.
 */
abstract class ContentController extends Controller
{
    /** @var class-string<Model> */
    protected string $modelClass;

    /** Permission prefix, e.g. "services" → services.view / services.update … */
    protected string $resource;

    /** Route name prefix, e.g. "admin.services" */
    protected string $routeName;

    /** View folder, e.g. "admin.services" */
    protected string $viewPath;

    protected string $label = 'Item';

    protected string $labelPlural = 'Items';

    protected array $searchColumns = ['title'];

    protected array $listWith = [];

    /** Extra columns the index may be sorted by. */
    protected array $sortable = [];

    /** Index table: [label, attribute (dot path), type: title|status|date|text|badge|bool|seo] */
    protected array $columns = [];

    /** Request keys handled separately from mass-assignment. */
    protected array $nonFillable = ['seo', 'faq_ids', 'page_faqs', 'new_faq_question', 'new_faq_answer', 'new_faq_category_id', 'new_faqs', 'links', 'blocks_json', 'tags', 'service_ids', 'technologies_text', 'local_facts_text', 'action'];

    /** @return class-string<FormRequest> */
    abstract protected function requestClass(): string;

    protected function formData(Model $item): array
    {
        return [];
    }

    protected function defaults(): array
    {
        return ['status' => ContentStatus::Draft->value];
    }

    protected function beforeSave(Model $item, FormRequest $request): void {}

    protected function afterSave(Model $item, FormRequest $request): void {}

    protected function query()
    {
        $class = $this->modelClass;

        return $class::query();
    }

    public function index(Request $request)
    {
        $this->authorizePermission('view');

        $query = $this->query()->with($this->listWith);
        if (method_exists($this->modelClass, 'seo')) {
            $query->with('seo:id,seoable_type,seoable_id,meta_title,meta_description,robots_index');
        }

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($w) use ($q) {
                foreach ($this->searchColumns as $col) {
                    $w->orWhere($col, 'like', '%'.addcslashes($q, '%_\\').'%');
                }
            });
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($request->query('trashed') && $this->usesSoftDeletes()) {
            $query->onlyTrashed();
        }
        $this->applyFilters($query, $request);

        // Whitelisted sort columns only — never pass raw input to orderBy.
        $sort = in_array($request->query('sort'), array_merge(['updated_at', 'created_at'], $this->sortable), true)
            ? $request->query('sort') : 'updated_at';
        $perPage = min(100, max(10, (int) $request->query('per_page', 25)));

        $items = $query->orderBy($sort, $request->query('dir') === 'asc' ? 'asc' : 'desc')
            ->paginate($perPage)->withQueryString();

        return view($this->indexView(), [
            'items' => $items,
            'columns' => $this->columns,
            'ctrl' => $this->meta(),
            'filters' => $this->filterOptions(),
        ]);
    }

    protected function indexView(): string
    {
        return view()->exists($this->viewPath.'.index') ? $this->viewPath.'.index' : 'admin.content.index';
    }

    protected function applyFilters($query, Request $request): void {}

    protected function filterOptions(): array
    {
        return [];
    }

    public function create()
    {
        $this->authorizePermission('create');
        $class = $this->modelClass;

        return $this->form(new $class($this->defaults()));
    }

    public function store()
    {
        $this->authorizePermission('create');
        $request = app($this->requestClass());
        $class = $this->modelClass;
        $item = new $class;

        $this->persist($item, $request);
        ActivityLog::record('created', $item, $this->label.' created');

        return redirect()->route($this->routeName.'.edit', $item->getKey())
            ->with('success', $this->label.' created.');
    }

    public function edit(int $id)
    {
        $item = $this->find($id);
        $this->authorizeItem('update', $item);

        return $this->form($item);
    }

    public function update(int $id)
    {
        $item = $this->find($id);
        $this->authorizeItem('update', $item);
        $request = app($this->requestClass());

        $this->persist($item, $request);
        ActivityLog::record('updated', $item, $this->label.' updated', ['changed' => array_keys($item->getChanges())]);

        if ($request->input('action') === 'preview' && method_exists($item, 'generatePreviewToken')) {
            return redirect()->route($this->routeName.'.preview', $item->getKey());
        }

        return back()->with('success', $this->label.' saved.');
    }

    public function destroy(int $id)
    {
        $item = $this->find($id);
        $this->authorizeItem('delete', $item);
        $item->delete();
        ActivityLog::record('deleted', $item, $this->label.' deleted');

        return redirect()->route($this->routeName.'.index')->with('success', $this->label.' moved to trash.');
    }

    public function restore(int $id)
    {
        $item = $this->find($id);
        $this->authorizeItem('delete', $item);
        $item->restore();
        ActivityLog::record('restored', $item);

        return back()->with('success', $this->label.' restored.');
    }

    public function publish(int $id)
    {
        $item = $this->find($id);
        $this->authorizeItem('publish', $item);
        $item->publish();
        ActivityLog::record('published', $item);

        return back()->with('success', $this->label.' published.');
    }

    public function unpublish(int $id)
    {
        $item = $this->find($id);
        $this->authorizeItem('publish', $item);
        $item->unpublish();
        ActivityLog::record('unpublished', $item);

        return back()->with('success', $this->label.' unpublished.');
    }

    public function preview(int $id)
    {
        $item = $this->find($id);
        $this->authorizeItem('update', $item);
        $token = $item->generatePreviewToken((int) config('site.preview_hours', 72));

        return redirect()->away(route('preview', [$item->getMorphClass(), $token]));
    }

    public function duplicate(int $id)
    {
        $this->authorizePermission('create');
        $source = $this->find($id);
        $copy = $source->replicate(['preview_token', 'preview_expires_at', 'published_at']);

        foreach (['title', 'name'] as $f) {
            if (isset($copy->{$f})) {
                $copy->{$f} .= ' (copy)';
            }
        }
        if (isset($copy->slug)) {
            $base = $source->slug.'-copy';
            $slug = $base;
            $i = 2;
            while (UrlSlug::where('slug', $slug)->exists() || $this->query()->withoutGlobalScopes()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i++;
            }
            $copy->slug = $slug;
        }
        if (isset($copy->status)) {
            $copy->status = ContentStatus::Draft;
        }
        if (isset($copy->service_id) && isset($copy->page_type)) {
            return back()->with('error', 'Programmatic pages are unique per combination — use the generator instead.');
        }

        DB::transaction(function () use ($source, $copy) {
            $copy->save();
            if (method_exists($source, 'seo') && $source->seo) {
                $copy->syncSeo(array_merge($source->seo->only($source->seo->getFillable()), ['canonical_url' => null]));
            }
            if (method_exists($source, 'blocks')) {
                foreach ($source->blocks as $b) {
                    $copy->blocks()->create($b->only(['type', 'data', 'sort_order', 'is_visible']));
                }
            }
            if (method_exists($source, 'faqs')) {
                $copy->syncFaqs($source->faqs->pluck('id')->all());
            }
        });
        ActivityLog::record('duplicated', $copy, 'Duplicated from #'.$source->getKey());

        return redirect()->route($this->routeName.'.edit', $copy->getKey())->with('success', 'Copy created as draft.');
    }

    protected function persist(Model $item, FormRequest $request): void
    {
        DB::transaction(function () use ($item, $request) {
            $data = collect($request->validated())->except($this->nonFillable)->all();

            if (array_key_exists('status', $data)) {
                $data = $this->resolveStatus($item, $data);
            }

            $item->fill($data);
            $this->beforeSave($item, $request);
            $item->save();

            if (method_exists($item, 'syncSeo') && $request->has('seo') && $request->user()->can('seo.edit')) {
                $item->syncSeo($request->validated('seo') ?? []);
            }
            if (method_exists($item, 'syncFaqs') && ($request->has('faq_ids_present') || $request->has('page_faqs_present'))) {
                $faqIds = (array) $request->input('faq_ids', []);

                // Direct page_faqs list from repeater
                if ($request->has('page_faqs') && is_array($request->input('page_faqs'))) {
                    $faqIds = [];
                    foreach ($request->input('page_faqs') as $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        $q = trim((string) ($row['question'] ?? ''));
                        $a = trim((string) ($row['answer'] ?? ''));
                        if ($q === '' && $a === '') {
                            continue;
                        }

                        $id = ! empty($row['id']) ? (int) $row['id'] : null;
                        if ($id) {
                            $faq = Faq::find($id);
                            if ($faq) {
                                if ($q !== '' || $a !== '') {
                                    $faq->update([
                                        'question' => $q ?: $faq->question,
                                        'answer' => $a ?: $faq->answer,
                                    ]);
                                }
                                $faqIds[] = $faq->id;
                                continue;
                            }
                        }

                        // Create new FAQ directly
                        if ($q !== '') {
                            $created = Faq::create([
                                'question' => $q,
                                'answer' => $a,
                                'is_active' => true,
                                'sort_order' => 0,
                            ]);
                            $faqIds[] = $created->id;
                        }
                    }
                } else {
                    // Inline single new FAQ submitted with form
                    if ($request->filled('new_faq_question') && $request->filled('new_faq_answer')) {
                        $newFaq = Faq::create([
                            'question' => trim((string) $request->input('new_faq_question')),
                            'answer' => trim((string) $request->input('new_faq_answer')),
                            'faq_category_id' => $request->input('new_faq_category_id') ?: null,
                            'is_active' => true,
                            'sort_order' => 0,
                        ]);
                        $faqIds[] = $newFaq->id;
                    }

                    // Inline multiple new_faqs submitted with form
                    if (is_array($request->input('new_faqs'))) {
                        foreach ($request->input('new_faqs') as $row) {
                            if (! empty($row['question']) && ! empty($row['answer'])) {
                                $newFaq = Faq::create([
                                    'question' => trim((string) $row['question']),
                                    'answer' => trim((string) $row['answer']),
                                    'faq_category_id' => ! empty($row['faq_category_id']) ? (int) $row['faq_category_id'] : null,
                                    'is_active' => true,
                                    'sort_order' => 0,
                                ]);
                                $faqIds[] = $newFaq->id;
                            }
                        }
                    }
                }

                $item->syncFaqs($faqIds);
            }
            if (method_exists($item, 'syncInternalLinks') && $request->has('links_present')) {
                $item->syncInternalLinks((array) $request->validated('links', []));
            }
            if (method_exists($item, 'syncBlocks') && $request->has('blocks_json')) {
                $item->syncBlocks(json_decode((string) $request->validated('blocks_json'), true) ?: []);
            }

            $this->afterSave($item, $request);
        });
    }

    /**
     * Users without "{resource}.publish" can only save drafts. A future publish date becomes "scheduled".
     */
    protected function resolveStatus(Model $item, array $data): array
    {
        $wanted = ContentStatus::from($data['status']);
        $canPublish = request()->user()->can($this->resource.'.publish');

        if (in_array($wanted, [ContentStatus::Published, ContentStatus::Scheduled], true) && ! $canPublish) {
            $data['status'] = $item->exists && $item->status ? $item->status->value : ContentStatus::Draft->value;
            session()->flash('warning', 'Saved — but you need publish permission to make it live.');

            return $data;
        }

        $at = isset($data['published_at']) ? Carbon::parse($data['published_at']) : null;
        if ($wanted === ContentStatus::Published && $at && $at->isFuture()) {
            $data['status'] = ContentStatus::Scheduled->value;
        }
        if ($wanted === ContentStatus::Scheduled && (! $at || $at->isPast())) {
            $data['status'] = ContentStatus::Published->value;
        }
        if (in_array($data['status'], [ContentStatus::Published->value, ContentStatus::Scheduled->value], true) && ! $at && $item->isFillable('published_at')) {
            $data['published_at'] = $item->published_at ?? now();
        }

        return $data;
    }

    protected function form(Model $item)
    {
        if ($item->exists) {
            foreach (['seo.ogImage', 'seo.twitterImage', 'blocks', 'faqs', 'internalLinks'] as $rel) {
                $root = explode('.', $rel)[0];
                if (method_exists($item, $root)) {
                    $item->loadMissing($rel);
                }
            }
        }

        return view($this->viewPath.'.form', [
            'item' => $item,
            'ctrl' => $this->meta(),
            'audit' => $item->exists && method_exists($item, 'seo') ? app(SeoAuditService::class)->audit($item) : null,
            'allFaqs' => method_exists($item, 'faqs') ? Faq::active()->orderBy('sort_order')->orderBy('question')->get(['id', 'question']) : collect(),
        ] + $this->formData($item));
    }

    protected function find(int $id): Model
    {
        $query = $this->query();
        if ($this->usesSoftDeletes()) {
            $query->withTrashed();
        }

        return $query->findOrFail($id);
    }

    protected function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->modelClass), true);
    }

    protected function authorizePermission(string $action): void
    {
        abort_unless(request()->user()->can($this->resource.'.'.$action), 403);
    }

    /** Uses the model's policy when one is registered, else the "{resource}.{ability}" permission. */
    protected function authorizeItem(string $ability, Model $item): void
    {
        if (Gate::getPolicyFor($item)) {
            $this->authorize($ability, $item);
        } else {
            $this->authorizePermission($ability);
        }
    }

    protected function meta(): array
    {
        $class = $this->modelClass;

        return [
            'label' => $this->label,
            'plural' => $this->labelPlural,
            'route' => $this->routeName,
            'resource' => $this->resource,
            'softDeletes' => $this->usesSoftDeletes(),
            'publishable' => method_exists($class, 'publish'),
            'previewable' => method_exists($class, 'generatePreviewToken'),
            'morph' => (new $class)->getMorphClass(),
        ];
    }
}
