<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;

class PageController extends ContentController
{
    protected string $modelClass = Page::class;

    protected string $resource = 'pages';

    protected string $routeName = 'admin.pages';

    protected string $viewPath = 'admin.pages';

    protected string $label = 'Page';

    protected string $labelPlural = 'Pages';

    protected array $sortable = ['title', 'published_at'];

    protected array $listWith = ['parent:id,title'];

    protected array $columns = [
        ['Title', 'title', 'title'],
        ['Template', 'template', 'badge'],
        ['Parent', 'parent.title', 'text'],
        ['SEO', 'seo', 'seo'],
        ['Status', 'status', 'status'],
        ['Updated', 'updated_at', 'date'],
    ];

    protected function requestClass(): string
    {
        return PageRequest::class;
    }

    protected function defaults(): array
    {
        return parent::defaults() + ['template' => 'default', 'hero_layout' => 'split', 'show_in_search' => true];
    }

    protected function formData(Model $item): array
    {
        return ['parents' => Page::whereKeyNot($item->id)->orderBy('title')->pluck('title', 'id')];
    }
}
