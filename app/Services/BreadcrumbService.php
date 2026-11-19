<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\CaseStudy;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds Home > … > Current trails from the content hierarchy. Rendered as <x-breadcrumb> and BreadcrumbList schema.
 */
class BreadcrumbService
{
    /** @return array<int, array{name: string, url: string|null}> */
    public function for(Model $model): array
    {
        $trail = [['name' => 'Home', 'url' => slash_url('/')]];

        $add = function (?Model $m, ?string $label = null) use (&$trail) {
            if ($m) {
                $trail[] = ['name' => $label ?? ($m->menu_label ?: ($m->name ?? $m->title)), 'url' => $m->url];
            }
        };

        switch (true) {
            case $model instanceof Page:
                $ancestors = [];
                $p = $model->parent;
                while ($p && count($ancestors) < 5) {
                    array_unshift($ancestors, $p);
                    $p = $p->parent;
                }
                foreach ($ancestors as $a) {
                    $add($a, $a->title);
                }
                break;

            case $model instanceof Service:
                $add($model->category);
                $add($model->parent);
                break;

            case $model instanceof LandingPage:
                $service = $model->service;
                $add($service?->category);
                $add($service);
                break;

            case $model instanceof Blog:
                $trail[] = ['name' => 'Blog', 'url' => sroute('blog.index')];
                if ($model->category) {
                    $trail[] = ['name' => $model->category->name, 'url' => $model->category->url];
                }
                break;

            case $model instanceof Portfolio:
                $trail[] = ['name' => 'Portfolio', 'url' => sroute('portfolio.index')];
                break;

            case $model instanceof CaseStudy:
                $trail[] = ['name' => 'Case Studies', 'url' => sroute('case-studies.index')];
                break;

            case $model instanceof ServiceCategory:
                break;
        }

        $trail[] = ['name' => $model->getAttribute('menu_label') ?: ($model->title ?? $model->name), 'url' => null];

        return $trail;
    }
}
