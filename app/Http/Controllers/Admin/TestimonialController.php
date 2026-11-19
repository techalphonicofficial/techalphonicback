<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\TestimonialRequest;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;

class TestimonialController extends ContentController
{
    protected string $modelClass = Testimonial::class;

    protected string $resource = 'testimonials';

    protected string $routeName = 'admin.testimonials';

    protected string $viewPath = 'admin.testimonials';

    protected string $label = 'Testimonial';

    protected string $labelPlural = 'Testimonials';

    protected array $searchColumns = ['name', 'company', 'review'];

    protected array $sortable = ['name', 'sort_order'];

    protected array $columns = [
        ['Name', 'name', 'title'],
        ['Company', 'company', 'text'],
        ['Rating', 'rating', 'text'],
        ['Active', 'is_active', 'bool'],
        ['Order', 'sort_order', 'text'],
    ];

    protected function requestClass(): string
    {
        return TestimonialRequest::class;
    }

    protected function defaults(): array
    {
        return ['is_active' => true, 'rating' => 5];
    }

    protected function formData(Model $item): array
    {
        return ['services' => Service::orderBy('name')->pluck('name', 'id')];
    }
}
