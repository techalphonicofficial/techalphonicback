<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Enums\ContentStatus;
use App\Models\PageBlock;
use App\Models\UrlSlug;
use App\Services\SchemaService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Shared validation for CMS content: slug format/uniqueness, SEO tab, publishing, FAQs, blocks, internal links.
 */
trait ContentRules
{
    public const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    protected function prepareSlug(string $from = 'title'): void
    {
        $slug = $this->input('slug') ?: $this->input($from);
        if ($slug) {
            $this->merge(['slug' => Str::slug(Str::lower((string) $slug))]);
        }
    }

    /**
     * @param  string  $table  model table for the per-table unique check
     * @param  bool  $flat  served at /{slug}/ — must also be free in the cross-type registry
     */
    /** The model being edited (admin routes use {id}), or null when creating. */
    protected function routeModel(): ?Model
    {
        $id = $this->route('id');
        if (! $id || ! method_exists($this, 'modelClass')) {
            return null;
        }
        $class = $this->modelClass();
        $query = in_array(SoftDeletes::class, class_uses_recursive($class), true)
            ? $class::withTrashed() : $class::query();

        return $query->find($id);
    }

    protected function slugRules(string $table, bool $flat = true): array
    {
        $model = $this->routeModel();
        $id = $model?->getKey();
        $type = $model?->getMorphClass() ?? (method_exists($this, 'modelClass') ? (new ($this->modelClass()))->getMorphClass() : null);

        return [
            'required', 'string', 'max:191', 'regex:'.self::SLUG_REGEX,
            Rule::unique($table, 'slug')->ignore($id),
            function (string $attr, $value, Closure $fail) use ($flat, $type, $id) {
                if ($value === config('site.admin_path')) {
                    $fail('This slug is reserved.');
                }
                if ($flat && ! UrlSlug::isAvailable($value, $type, $id)) {
                    $fail('This URL is already used by another page or is reserved.');
                }
            },
        ];
    }

    protected function publishingRules(): array
    {
        return [
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date'],
        ];
    }

    protected function seoRules(): array
    {
        return [
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
            'seo.meta_keywords' => ['nullable', 'string', 'max:500'],
            'seo.canonical_url' => ['nullable', 'string', 'max:500', 'regex:#^(https?://|/)#'],
            'seo.robots_index' => ['nullable', 'boolean'],
            'seo.robots_follow' => ['nullable', 'boolean'],
            'seo.og_title' => ['nullable', 'string', 'max:255'],
            'seo.og_description' => ['nullable', 'string', 'max:500'],
            'seo.og_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'seo.twitter_title' => ['nullable', 'string', 'max:255'],
            'seo.twitter_description' => ['nullable', 'string', 'max:500'],
            'seo.twitter_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'seo.schema_type' => ['nullable', 'string', 'max:50'],
            'seo.schema_enabled' => ['nullable', 'boolean'],
            'seo.schema_json' => ['nullable', 'string', 'max:20000', function ($a, $v, $fail) {
                if ($error = SchemaService::validateJson($v)) {
                    $fail($error);
                }
            }],
            'seo.focus_keyword' => ['nullable', 'string', 'max:255'],
            'seo.secondary_keywords' => ['nullable', 'string', 'max:500'],
            'seo.include_in_sitemap' => ['nullable', 'boolean'],
            'seo.sitemap_priority' => ['nullable', 'numeric', 'between:0,1'],
            'seo.sitemap_changefreq' => ['nullable', Rule::in(['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])],
        ];
    }

    protected function relationRules(): array
    {
        return [
            'faq_ids' => ['nullable', 'array'],
            'faq_ids.*' => ['integer', 'exists:faqs,id'],
            'page_faqs' => ['nullable', 'array'],
            'page_faqs.*.id' => ['nullable', 'integer'],
            'page_faqs.*.question' => ['nullable', 'string', 'max:500'],
            'page_faqs.*.answer' => ['nullable', 'string'],
            'new_faq_question' => ['nullable', 'string', 'max:500'],
            'new_faq_answer' => ['nullable', 'string'],
            'new_faq_category_id' => ['nullable', 'integer', 'exists:faq_categories,id'],
            'new_faqs' => ['nullable', 'array'],
            'new_faqs.*.question' => ['nullable', 'string', 'max:500'],
            'new_faqs.*.answer' => ['nullable', 'string'],
            'new_faqs.*.faq_category_id' => ['nullable', 'integer', 'exists:faq_categories,id'],
            'links' => ['nullable', 'array', 'max:20'],
            'links.*.anchor' => ['nullable', 'string', 'max:120'],
            'links.*.url' => ['nullable', 'string', 'max:500'],
            'blocks_json' => ['nullable', 'json', function ($a, $v, $fail) {
                $blocks = json_decode((string) $v, true) ?: [];
                foreach ($blocks as $b) {
                    if (! isset(PageBlock::TYPES[$b['type'] ?? ''])) {
                        $fail('Unknown block type: '.e($b['type'] ?? '?'));
                    }
                    if (($b['type'] ?? '') === 'custom_html' && ! $this->user()->can('custom_html.use')) {
                        $fail('You do not have permission to use Custom HTML blocks.');
                    }
                }
            }],
        ];
    }

    /** Normalises checkbox-driven SEO booleans (unchecked boxes are absent from the request). */
    protected function prepareSeoBooleans(): void
    {
        if (! $this->has('seo')) {
            return;
        }
        $seo = (array) $this->input('seo');
        foreach (['robots_index', 'robots_follow', 'schema_enabled', 'include_in_sitemap'] as $flag) {
            $seo[$flag] = filter_var($seo[$flag] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
        $this->merge(['seo' => $seo]);
    }

    /** Admin JSON repeaters (benefits/process/...) arrive as arrays of rows; drop empty rows. */
    protected function cleanRepeater(string $key): void
    {
        if ($this->has($key)) {
            $rows = collect((array) $this->input($key))
                ->map(fn ($r) => is_array($r) ? array_map(fn ($v) => is_string($v) ? trim($v) : $v, $r) : trim((string) $r))
                ->filter(fn ($r) => is_array($r) ? collect($r)->filter(fn ($v) => $v !== '' && $v !== null)->isNotEmpty() : $r !== '')
                ->values()->all();
            $this->merge([$key => $rows ?: null]);
        }
    }

    protected function repeaterRules(string $key, array $fields = ['title', 'text', 'icon']): array
    {
        $rules = [$key => ['nullable', 'array', 'max:50']];
        foreach ($fields as $f) {
            $rules["$key.*.$f"] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }
}
