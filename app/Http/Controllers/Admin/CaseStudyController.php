<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CaseStudyRequest;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;

class CaseStudyController extends ContentController
{
    protected string $modelClass = CaseStudy::class;

    protected string $resource = 'case_studies';

    protected string $routeName = 'admin.case-studies';

    protected string $viewPath = 'admin.case-studies';

    protected string $label = 'Case study';

    protected string $labelPlural = 'Case Studies';

    protected array $searchColumns = ['title', 'client'];

    protected array $sortable = ['title', 'sort_order'];

    protected array $listWith = ['industry:id,name'];

    protected array $columns = [
        ['Title', 'title', 'title'],
        ['Client', 'client', 'text'],
        ['Industry', 'industry.name', 'text'],
        ['Status', 'status', 'status'],
        ['Order', 'sort_order', 'text'],
    ];

    protected function requestClass(): string
    {
        return CaseStudyRequest::class;
    }

    protected function formData(Model $item): array
    {
        return [
            'industries' => Industry::orderBy('name')->pluck('name', 'id'),
            'services' => Service::orderBy('name')->pluck('name', 'id'),
            'testimonials' => Testimonial::orderBy('name')->get(['id', 'name', 'company'])->mapWithKeys(fn ($t) => [$t->id => $t->name.($t->company ? ' — '.$t->company : '')]),
        ];
    }
}
