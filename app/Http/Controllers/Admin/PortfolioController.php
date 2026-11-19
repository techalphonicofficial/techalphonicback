<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PortfolioRequest;
use App\Models\Industry;
use App\Models\Portfolio;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;

class PortfolioController extends ContentController
{
    protected string $modelClass = Portfolio::class;

    protected string $resource = 'portfolio';

    protected string $routeName = 'admin.portfolio';

    protected string $viewPath = 'admin.portfolio';

    protected string $label = 'Project';

    protected string $labelPlural = 'Portfolio';

    protected array $searchColumns = ['title', 'client', 'category'];

    protected array $sortable = ['title', 'sort_order'];

    protected array $columns = [
        ['Title', 'title', 'title'],
        ['Client', 'client', 'text'],
        ['Category', 'category', 'badge'],
        ['Status', 'status', 'status'],
        ['Order', 'sort_order', 'text'],
    ];

    protected function requestClass(): string
    {
        return PortfolioRequest::class;
    }

    protected function formData(Model $item): array
    {
        return [
            'industries' => Industry::orderBy('name')->pluck('name', 'id'),
            'services' => Service::orderBy('name')->pluck('name', 'id'),
            'categories' => Portfolio::whereNotNull('category')->distinct()->pluck('category'),
        ];
    }
}
