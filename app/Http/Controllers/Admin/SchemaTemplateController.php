<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SchemaTemplateRequest;
use App\Models\SchemaTemplate;
use App\Services\SchemaService;
use App\Services\TemplateRenderer;
use Illuminate\Database\Eloquent\Model;

/**
 * Reusable JSON-LD templates applied to every page of a type (e.g. a Review snippet for all services).
 * Global Organization / WebSite schema is generated from Settings; per-page overrides live in each page's SEO tab.
 */
class SchemaTemplateController extends ContentController
{
    protected string $modelClass = SchemaTemplate::class;

    protected string $resource = 'schema';

    protected string $routeName = 'admin.schemas';

    protected string $viewPath = 'admin.schemas';

    protected string $label = 'Schema template';

    protected string $labelPlural = 'Schema Templates';

    protected array $searchColumns = ['name', 'type'];

    protected array $sortable = ['name', 'sort_order'];

    protected array $columns = [
        ['Name', 'name', 'title'],
        ['Type', 'type', 'badge'],
        ['Applies to', 'applies_to', 'badge'],
        ['Active', 'is_active', 'bool'],
    ];

    protected function requestClass(): string
    {
        return SchemaTemplateRequest::class;
    }

    protected function authorizePermission(string $action): void
    {
        abort_unless(request()->user()->can('schema.edit'), 403);
    }

    protected function authorizeItem(string $ability, Model $item): void
    {
        $this->authorizePermission($ability);
    }

    protected function defaults(): array
    {
        return ['is_active' => true, 'applies_to' => 'service', 'type' => 'Service', 'json_template' => "{\n  \"@type\": \"Service\",\n  \"name\": \"{{page_title}}\",\n  \"url\": \"{{page_url}}\"\n}"];
    }

    protected function formData(Model $item): array
    {
        return [
            'types' => SchemaTemplate::TYPES,
            'appliesTo' => SchemaTemplate::APPLIES_TO,
            'variables' => array_merge(['site_name', 'site_url', 'page_title', 'page_url', 'description', 'image', 'phone', 'email', 'address'], TemplateRenderer::VARIABLES),
            'globalPreview' => app(SchemaService::class)->toJson(app(SchemaService::class)->globalNodes()),
        ];
    }
}
