<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\TaxonomyRequest;
use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Model;

class FaqCategoryController extends ContentController
{
    protected string $modelClass = FaqCategory::class;

    protected string $resource = 'faqs';

    protected string $routeName = 'admin.faq-categories';

    protected string $viewPath = 'admin.taxonomy';

    protected string $label = 'FAQ category';

    protected string $labelPlural = 'FAQ Categories';

    protected array $searchColumns = ['name'];

    protected array $sortable = ['name', 'sort_order'];

    protected array $nonFillable = ['description', 'parent_id'];

    protected array $columns = [
        ['Name', 'name', 'title'],
        ['Slug', 'slug', 'text'],
        ['FAQs', 'faqs_count', 'text'],
        ['Order', 'sort_order', 'text'],
    ];

    protected function query()
    {
        return FaqCategory::query()->withCount('faqs');
    }

    protected function requestClass(): string
    {
        return TaxonomyRequest::class;
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function formData(Model $item): array
    {
        return ['withDescription' => false, 'parents' => collect()];
    }
}
