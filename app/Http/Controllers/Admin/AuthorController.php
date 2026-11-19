<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AuthorRequest;
use App\Models\Author;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuthorController extends ContentController
{
    protected string $modelClass = Author::class;

    protected string $resource = 'blogs';

    protected string $routeName = 'admin.authors';

    protected string $viewPath = 'admin.authors';

    protected string $label = 'Author';

    protected string $labelPlural = 'Authors';

    protected array $searchColumns = ['name'];

    protected array $sortable = ['name'];

    protected array $columns = [
        ['Name', 'name', 'title'],
        ['Designation', 'designation', 'text'],
        ['Posts', 'blogs_count', 'text'],
    ];

    protected function query()
    {
        return Author::query()->withCount('blogs');
    }

    protected function requestClass(): string
    {
        return AuthorRequest::class;
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function formData(Model $item): array
    {
        return ['users' => User::orderBy('name')->pluck('name', 'id')];
    }
}
