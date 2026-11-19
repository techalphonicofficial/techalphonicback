<?php

namespace App\Support;

/**
 * Single definition of permissions and default role grants (used by the seeder and the Roles screen).
 */
class Permissions
{
    public const CONTENT_RESOURCES = [
        'pages' => 'Pages & homepage',
        'services' => 'Services & categories',
        'landing_pages' => 'Programmatic SEO pages',
        'locations' => 'Locations',
        'industries' => 'Industries',
        'blogs' => 'Blogs',
        'portfolio' => 'Portfolio',
        'case_studies' => 'Case studies',
        'testimonials' => 'Testimonials',
        'faqs' => 'FAQs',
        'media' => 'Media library',
    ];

    public const ACTIONS = ['view', 'create', 'update', 'delete', 'publish'];

    public const SPECIAL = [
        'dashboard.view' => 'View dashboard',
        'seo.edit' => 'Edit SEO fields',
        'schema.edit' => 'Edit schema templates',
        'seo.bulk' => 'Bulk SEO editor & bulk generation',
        'leads.view' => 'View leads',
        'leads.view_all' => 'View all leads (not just assigned)',
        'leads.manage' => 'Update leads',
        'leads.delete' => 'Delete leads',
        'leads.export' => 'Export leads',
        'menus.manage' => 'Manage menus',
        'redirects.manage' => 'Manage redirects & sitemap import',
        'settings.manage' => 'Manage settings',
        'users.manage' => 'Manage users',
        'roles.manage' => 'Manage roles & permissions',
        'custom_html.use' => 'Use Custom HTML blocks',
        'activity.view' => 'View activity log',
        'system.maintenance' => 'Run maintenance commands (caches, storage link, migrations)',
        'ai.configure' => 'Configure AI provider & API keys',
        'ai.generate' => 'Write page content with AI',
    ];

    public static function all(): array
    {
        $out = [];
        foreach (array_keys(self::CONTENT_RESOURCES) as $r) {
            foreach (self::ACTIONS as $a) {
                $out[] = "$r.$a";
            }
        }

        return array_merge($out, array_keys(self::SPECIAL));
    }

    /** @return array<string, array<int, string>> role => permissions ("*" = all) */
    public static function defaultRoles(): array
    {
        $content = fn (array $resources, array $actions = self::ACTIONS) => collect($resources)
            ->flatMap(fn ($r) => collect($actions)->map(fn ($a) => "$r.$a"))->all();
        $allContent = array_keys(self::CONTENT_RESOURCES);

        return [
            'Super Admin' => ['*'],
            // Server commands stay Super Admin only unless granted on the Roles screen.
            'Admin' => array_values(array_diff(self::all(), ['roles.manage', 'system.maintenance'])),
            'SEO Manager' => array_merge(
                $content($allContent, ['view', 'create', 'update', 'publish']),
                ['dashboard.view', 'seo.edit', 'schema.edit', 'seo.bulk', 'redirects.manage', 'menus.manage', 'leads.view', 'ai.generate']
            ),
            'Content Manager' => array_merge(
                $content(['pages', 'services', 'blogs', 'portfolio', 'case_studies', 'testimonials', 'faqs', 'media', 'landing_pages', 'industries', 'locations'], ['view', 'create', 'update', 'publish']),
                ['dashboard.view', 'seo.edit', 'ai.generate']
            ),
            'Editor' => array_merge(
                $content(['pages', 'services', 'blogs', 'portfolio', 'case_studies', 'testimonials', 'faqs', 'media', 'landing_pages'], ['view', 'create', 'update']),
                ['dashboard.view']
            ),
            'Sales' => ['dashboard.view', 'leads.view', 'leads.manage'],
        ];
    }
}
