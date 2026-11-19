<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\LandingPage;
use App\Models\Media;
use App\Models\SeoMeta;
use App\Support\SeoData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Resolves page metadata: admin overrides first, then intelligent defaults from page content, then site defaults.
 */
class SeoService
{
    public function __construct(
        private SettingsService $settings,
        private TemplateRenderer $renderer,
        private BreadcrumbService $breadcrumbs,
        private SchemaService $schema,
    ) {}

    public function forModel(Model $model, array $overrides = [], bool $isPreview = false): SeoData
    {
        /** @var SeoMeta|null $meta */
        $meta = $model->relationLoaded('seo') ? $model->seo : (method_exists($model, 'seo') ? $model->seo()->with(['ogImage', 'twitterImage'])->first() : null);
        $vars = $model instanceof LandingPage ? $model->templateVariables() : [];
        $r = fn (?string $v) => $v ? trim($this->renderer->render($v, $vars)) : null;

        $name = $model->heading ?? $model->title ?? $model->name ?? '';
        $title = $r($meta?->meta_title) ?: $this->composeTitle($r($model->title ?? $model->name ?? $name));
        $description = $r($meta?->meta_description) ?: $this->excerptFrom($model, $vars);
        $canonical = $meta?->canonical_url ? $this->absolute($meta->canonical_url) : ($model->url ?? slash_url('/'.request()->path()));
        $image = $meta?->ogImage?->url ?: $this->featuredImageUrl($model) ?: $this->defaultImage();

        $robots = $meta ? $meta->robotsContent() : 'index, follow';
        if ($isPreview || $this->settings->bool('discourage_indexing')) {
            $robots = 'noindex, nofollow';
        }

        $data = new SeoData(
            title: $title,
            description: $description,
            keywords: $meta?->meta_keywords ? $r($meta->meta_keywords) : null,
            canonical: $canonical,
            robots: $robots,
            ogTitle: $r($meta?->og_title) ?: $title,
            ogDescription: $r($meta?->og_description) ?: $description,
            ogImage: $image,
            ogType: $model instanceof Blog ? 'article' : 'website',
            twitterTitle: $r($meta?->twitter_title) ?: $r($meta?->og_title) ?: $title,
            twitterDescription: $r($meta?->twitter_description) ?: $description,
            twitterImage: $meta?->twitterImage?->url ?: $image,
            publishedTime: $model->published_at?->toIso8601String(),
            modifiedTime: $model->updated_at?->toIso8601String(),
        );

        foreach ($overrides as $k => $v) {
            $data->{$k} = $v;
        }

        $data->breadcrumbs = $data->breadcrumbs ?: $this->breadcrumbs->for($model);
        $data->schema = $this->schema->forModel($model, $data, $meta);

        return $data;
    }

    /** For listing / system pages without a model (blog index, search, 404). */
    public function make(string $title, ?string $description = null, array $breadcrumbs = [], array $extra = []): SeoData
    {
        $data = new SeoData(
            title: $this->composeTitle($title),
            description: $description ?: $this->settings->get('default_meta_description'),
            canonical: $extra['canonical'] ?? slash_url('/'.request()->path()),
            robots: $extra['robots'] ?? ($this->settings->bool('discourage_indexing') ? 'noindex, nofollow' : 'index, follow'),
            ogImage: $this->defaultImage(),
            breadcrumbs: $breadcrumbs,
        );
        $data->ogTitle = $data->twitterTitle = $data->title;
        $data->ogDescription = $data->twitterDescription = $data->description;
        $data->twitterImage = $data->ogImage;
        foreach ($extra as $k => $v) {
            if (property_exists($data, $k)) {
                $data->{$k} = $v;
            }
        }
        $data->schema = $this->schema->forListing($data, $extra['schema_type'] ?? 'WebPage', $extra['items'] ?? []);

        return $data;
    }

    public function composeTitle(?string $title): string
    {
        $site = (string) $this->settings->get('site_name');
        $title = trim((string) $title);
        if ($title === '' || $title === $site) {
            return $this->settings->get('default_meta_title') ?: $site;
        }
        if (Str::contains($title, $site)) {
            return $title;
        }
        $full = $title.' '.$this->settings->get('title_separator', '|').' '.$site;

        // Keep within ~60 chars when possible; drop the brand suffix rather than truncating the keyword.
        return mb_strlen($full) <= 65 ? $full : $title;
    }

    private function excerptFrom(Model $model, array $vars): ?string
    {
        foreach (['excerpt', 'short_description', 'introduction', 'hero_description', 'description', 'summary', 'content'] as $field) {
            $value = $model->getAttribute($field);
            if (is_string($value) && trim(strip_tags($value)) !== '') {
                $text = trim(preg_replace('/\s+/', ' ', strip_tags($this->renderer->render($value, $vars))));

                return Str::limit($text, 155, '…');
            }
        }

        return $this->settings->get('default_meta_description');
    }

    private function featuredImageUrl(Model $model): ?string
    {
        foreach (['featuredImage', 'heroImage', 'image'] as $rel) {
            if (method_exists($model, $rel)) {
                $media = $model->relationLoaded($rel) ? $model->{$rel} : $model->{$rel}()->first();
                if ($media instanceof Media) {
                    return $media->url;
                }
            }
        }

        return null;
    }

    private function defaultImage(): ?string
    {
        return $this->settings->media('default_og_image')?->url ?: $this->settings->media('logo')?->url;
    }

    private function absolute(string $url): string
    {
        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }
}
