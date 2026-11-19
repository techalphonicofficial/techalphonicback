<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Permission-backed policy shared by all CMS models. Permission names are "{resource}.{action}",
 * e.g. "services.update", "landing_pages.publish". Super Admin bypasses via Gate::before.
 */
class ContentPolicy
{
    public const RESOURCES = [
        'page' => 'pages',
        'home_section' => 'pages',
        'hero_slide' => 'pages',
        'service' => 'services',
        'service_category' => 'services',
        'landing_page' => 'landing_pages',
        'location' => 'locations',
        'industry' => 'industries',
        'blog' => 'blogs',
        'portfolio' => 'portfolio',
        'case_study' => 'case_studies',
        'testimonial' => 'testimonials',
        'faq' => 'faqs',
        'media' => 'media',
        'redirect' => 'redirects',
        'menu_item' => 'menus',
    ];

    public static function resourceFor(Model|string $model): string
    {
        $alias = is_string($model) ? (new $model)->getMorphClass() : $model->getMorphClass();

        return self::RESOURCES[$alias] ?? $alias;
    }

    // Class-level checks (index / create) use the permission strings directly, e.g. can('services.create').

    public function view(User $user, Model $model): bool
    {
        return $user->can(self::resourceFor($model).'.view');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can(self::resourceFor($model).'.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can(self::resourceFor($model).'.delete');
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->delete($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    public function publish(User $user, Model $model): bool
    {
        return $user->can(self::resourceFor($model).'.publish');
    }
}
