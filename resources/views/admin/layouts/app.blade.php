@php
    $u = auth()->user();
    $adminPrefix = config('site.admin_path');

    // [label, route|[route, param], icon, permission, active-path pattern] grouped by section (null = top level).
    $navGroups = [
        null => [
            ['Dashboard', 'admin.dashboard', 'layout', 'dashboard.view', 'admin'],
        ],
        'Website' => [
            ['Homepage', 'admin.home.edit', 'home', 'pages.update', 'admin/home*'],
            ['Pages', 'admin.pages.index', 'file-text', 'pages.view', 'admin/pages*'],
            ['Menus', 'admin.menus.index', 'menu', 'menus.manage', 'admin/menus*'],
            ['Media Library', 'admin.media.index', 'image', 'media.view', 'admin/media*'],
        ],
        'Services' => [
            ['Services', 'admin.services.index', 'briefcase', 'services.view', 'admin/services*'],
            ['Service Categories', 'admin.service-categories.index', 'layers', 'services.view', 'admin/service-categories*'],
            ['Locations', 'admin.locations.index', 'map-pin', 'locations.view', 'admin/locations*'],
            ['Industries', 'admin.industries.index', 'building', 'industries.view', 'admin/industries*'],
        ],
        'Programmatic SEO' => [
            ['SEO Pages', 'admin.landing-pages.index', 'rocket', 'landing_pages.view', 'admin/landing-pages'],
            ['Generate Page', 'admin.landing-pages.create', 'plus', 'landing_pages.create', 'admin/landing-pages/create'],
            ['Bulk Generator', 'admin.landing-pages.bulk', 'zap', 'seo.bulk', 'admin/landing-pages/bulk'],
            ['Bulk SEO Editor', 'admin.landing-pages.bulk-seo', 'pen', 'seo.bulk', 'admin/landing-pages/bulk-seo'],
            ['AI Writing Log', 'admin.ai.generations', 'sparkles', 'ai.generate', 'admin/ai/generations*'],
        ],
        'Content' => [
            ['Blogs', 'admin.blogs.index', 'file-text', 'blogs.view', 'admin/blogs*'],
            ['Blog Categories', 'admin.blog-categories.index', 'layers', 'blogs.view', 'admin/blog-categories*'],
            ['Authors', 'admin.authors.index', 'users', 'blogs.view', 'admin/authors*'],
            ['FAQs', 'admin.faqs.index', 'message', 'faqs.view', 'admin/faqs*'],
            ['Testimonials', 'admin.testimonials.index', 'star', 'testimonials.view', 'admin/testimonials*'],
            ['Case Studies', 'admin.case-studies.index', 'trending-up', 'case_studies.view', 'admin/case-studies*'],
            ['Portfolio', 'admin.portfolio.index', 'award', 'portfolio.view', 'admin/portfolio*'],
        ],
        'Sales' => [
            ['Leads', 'admin.leads.index', 'target', 'leads.view', 'admin/leads*'],
        ],
        'SEO & Technical' => [
            ['Redirects', 'admin.redirects.index', 'link', 'redirects.manage', 'admin/redirects*'],
            ['Sitemap Import', 'admin.sitemap-import.index', 'globe', 'redirects.manage', 'admin/sitemap-import*'],
            ['Schema Templates', 'admin.schemas.index', 'code', 'schema.edit', 'admin/schemas*'],
            ['SEO Settings', ['admin.settings.edit', 'seo'], 'search', 'settings.manage', 'admin/settings/seo'],
        ],
        'Settings' => [
            ['Website Settings', ['admin.settings.edit', 'general'], 'settings', 'settings.manage', 'admin/settings/general'],
            ['Contact Settings', ['admin.settings.edit', 'contact'], 'phone', 'settings.manage', 'admin/settings/contact'],
            ['WhatsApp & Call', ['admin.settings.edit', 'whatsapp'], 'whatsapp', 'settings.manage', 'admin/settings/whatsapp'],
            ['Enquiry Popup & CTA', ['admin.settings.edit', 'popup'], 'message', 'settings.manage', 'admin/settings/popup'],
            ['Social Links', ['admin.settings.edit', 'social'], 'share', 'settings.manage', 'admin/settings/social'],
            ['Analytics & Tracking', ['admin.settings.edit', 'analytics'], 'chart', 'settings.manage', 'admin/settings/analytics'],
            ['AI Content', 'admin.ai.settings', 'sparkles', 'ai.configure', 'admin/ai/settings*'],
            ['Users', 'admin.users.index', 'users', 'users.manage', 'admin/users*'],
            ['Roles & Permissions', 'admin.roles.index', 'shield', 'roles.manage', 'admin/roles*'],
            ['Activity Log', 'admin.activity.index', 'clock', 'activity.view', 'admin/activity*'],
            ['Maintenance', 'admin.maintenance.index', 'wrench', 'system.maintenance', 'admin/maintenance*'],
        ],
    ];

    // Resolve links the user may see, and find the active item (drives breadcrumbs + which group is open).
    $sections = [];
    $activeLabel = null;
    $activeGroup = null;
    foreach ($navGroups as $group => $entries) {
        foreach ($entries as [$label, $route, $icon, $perm, $pattern]) {
            if (! $u->can($perm)) {
                continue;
            }
            $active = request()->is(preg_replace('#^admin#', $adminPrefix, $pattern));
            $sections[$group ?? ''][] = [
                'label' => $label,
                'href' => is_array($route) ? route($route[0], $route[1]) : route($route),
                'icon' => $icon,
                'active' => $active,
            ];
            if ($active && ! $activeLabel) {
                [$activeLabel, $activeGroup] = [$label, $group];
            }
        }
    }

    // "Create" menu + command palette shortcuts.
    $quickCreate = collect([
        ['New page', 'admin.pages.create', 'file-text', 'pages.create'],
        ['New service', 'admin.services.create', 'briefcase', 'services.create'],
        ['New SEO page', 'admin.landing-pages.create', 'rocket', 'landing_pages.create'],
        ['New blog post', 'admin.blogs.create', 'pen', 'blogs.create'],
        ['New case study', 'admin.case-studies.create', 'trending-up', 'case_studies.create'],
        ['New redirect', 'admin.redirects.create', 'link', 'redirects.manage'],
    ])->filter(fn ($c) => \Illuminate\Support\Facades\Route::has($c[1]) && $u->can($c[3]))
      ->map(fn ($c) => ['label' => $c[0], 'href' => route($c[1]), 'icon' => $c[2]])->values();

    $palette = collect($sections)->flatMap(fn ($items, $group) => collect($items)->map(fn ($i) => ['label' => $i['label'], 'href' => $i['href'], 'group' => $group ?: 'General']))
        ->concat($quickCreate->map(fn ($c) => ['label' => $c['label'], 'href' => $c['href'], 'group' => 'Create']))
        ->values();

    $newLeads = $u->can('leads.view') ? \Illuminate\Support\Facades\Cache::remember('admin.new-leads', 60, fn () => \App\Models\Lead::where('status', 'new')->count()) : 0;
    // Already HTML-escaped by Blade (inline @section values are escaped), so it is echoed with {!! !!}.
    $title = trim($__env->yieldContent('title', 'Dashboard'));
    $initials = collect(explode(' ', $u->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $isLocal = app()->environment('local');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{!! $title !!} · {{ setting('site_name') }} Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..700&family=JetBrains+Mono:wght@400;500&display=swap">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @stack('head')
</head>
<body x-data="adminShell({ palette: @js($palette), activeGroup: @js($activeGroup) })" :class="rail && 'rail'" @keydown.window="onKey($event)">
<div class="min-h-screen lg:flex">
    {{-- ================= Sidebar ================= --}}
    <aside class="a-sidebar fixed inset-y-0 left-0 z-40 flex -translate-x-full flex-col bg-night text-slate-300 transition-[transform,width] duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0" :class="nav && 'translate-x-0'" aria-label="Sidebar">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(120%_60%_at_0%_0%,rgb(83_175_190/0.14),transparent_60%)]" aria-hidden="true"></div>

        {{-- Brand --}}
        <div class="relative flex h-14 shrink-0 items-center gap-2.5 border-b border-white/[0.06] px-4">
            <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2.5">
                <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-white/[0.06] ring-1 ring-white/10">
                    <span class="grid grid-cols-2 gap-[3px]" aria-hidden="true"><span class="size-[7px] rounded-[2px] bg-teal"></span><span class="size-[7px] rounded-[2px] bg-coral"></span><span class="size-[7px] rounded-[2px] bg-amber"></span><span class="size-[7px] rounded-[2px] bg-steel"></span></span>
                </span>
                <span class="a-rail-hide min-w-0">
                    <span class="block truncate text-[0.875rem] font-semibold text-white">{{ setting('site_name') }}</span>
                    <span class="block text-[0.6875rem] text-slate-500">Admin console</span>
                </span>
            </a>
            <button type="button" class="ml-auto grid size-8 place-items-center rounded-md text-slate-400 hover:bg-white/[0.06] hover:text-white lg:hidden" @click="nav = false" aria-label="Close navigation"><x-icon name="x" class="size-4" /></button>
        </div>

        {{-- Quick jump --}}
        <div class="relative shrink-0 px-3 pt-3">
            <button type="button" @click="openPalette()" class="flex h-9 w-full items-center gap-2 rounded-lg border border-white/[0.08] bg-white/[0.04] px-2.5 text-[0.8125rem] text-slate-400 transition hover:border-white/15 hover:text-slate-200" title="Search (Ctrl+K)">
                <x-icon name="search" class="size-4 shrink-0" />
                <span class="a-rail-hide flex-1 text-left">Jump to…</span>
                <span class="a-rail-hide flex gap-0.5"><kbd class="rounded border border-white/10 bg-white/[0.06] px-1 text-[0.625rem] text-slate-400">Ctrl</kbd><kbd class="rounded border border-white/10 bg-white/[0.06] px-1 text-[0.625rem] text-slate-400">K</kbd></span>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="relative flex-1 overflow-y-auto px-3 pb-6 pt-1" aria-label="Admin">
            @foreach($sections as $group => $items)
                @if($group === '')
                    <div class="mt-2 grid gap-0.5">
                        @foreach($items as $i)@include('admin.partials.nav-link', ['i' => $i, 'newLeads' => $newLeads])@endforeach
                    </div>
                @else
                    <div>
                        <button type="button" class="a-nav-group a-rail-hide" @click="toggleGroup(@js($group))" :aria-expanded="isOpen(@js($group)).toString()">
                            {{ $group }}
                            <x-icon name="chevron-down" class="size-3 transition-transform" x-bind:class="isOpen({{ \Illuminate\Support\Js::from($group) }}) ? '' : '-rotate-90'" />
                        </button>
                        <div class="rail-divider mx-2 my-3 hidden h-px bg-white/[0.06]"></div>
                        <div class="grid gap-0.5" x-show="rail || isOpen(@js($group))" x-collapse>
                            @foreach($items as $i)@include('admin.partials.nav-link', ['i' => $i, 'newLeads' => $newLeads])@endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        {{-- User --}}
        <div class="relative shrink-0 border-t border-white/[0.06] p-3">
            <div class="flex items-center gap-2.5 rounded-lg p-1.5">
                <span class="a-avatar size-8 text-xs">{{ $initials }}</span>
                <span class="a-rail-hide min-w-0 flex-1">
                    <span class="block truncate text-[0.8125rem] font-medium text-white">{{ $u->name }}</span>
                    <span class="block truncate text-[0.6875rem] text-slate-500">{{ $u->getRoleNames()->first() ?? 'User' }}</span>
                </span>
                <form method="POST" action="{{ route('admin.logout') }}" class="a-rail-hide">@csrf
                    <button class="grid size-8 place-items-center rounded-md text-slate-400 transition hover:bg-white/[0.06] hover:text-white" title="Sign out" aria-label="Sign out"><x-icon name="log-out" class="size-4" /></button>
                </form>
            </div>
        </div>
    </aside>
    <div x-show="nav" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-night/60 backdrop-blur-sm lg:hidden" @click="nav = false"></div>

    {{-- ================= Main ================= --}}
    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-20 flex h-14 items-center gap-2 border-b border-line bg-white/85 px-4 backdrop-blur-md sm:px-6">
            <button type="button" class="a-btn a-btn-ghost a-btn-icon lg:hidden" @click="nav = true" aria-label="Open navigation"><x-icon name="menu" class="size-[18px]" /></button>
            <button type="button" class="a-btn a-btn-ghost a-btn-icon hidden lg:inline-flex" @click="toggleRail()" :title="rail ? 'Expand sidebar' : 'Collapse sidebar'" aria-label="Toggle sidebar"><x-icon name="panel-left" class="size-[18px]" /></button>
            <span class="mx-1 hidden h-5 w-px bg-line lg:block" aria-hidden="true"></span>

            {{-- Breadcrumbs --}}
            <nav class="flex min-w-0 flex-1 items-center gap-1.5 text-[0.8125rem]" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}" class="hidden text-slate-500 hover:text-ink sm:inline">Admin</a>
                @if($activeGroup)
                    <x-icon name="chevron-right" class="hidden size-3.5 text-slate-300 sm:inline" />
                    <span class="hidden text-slate-500 sm:inline">{{ $activeGroup }}</span>
                @endif
                @if($activeLabel && ! in_array(mb_strtolower($activeLabel), [mb_strtolower(html_entity_decode($title)), mb_strtolower((string) $activeGroup)], true))
                    <x-icon name="chevron-right" class="hidden size-3.5 text-slate-300 sm:inline" />
                    <span class="hidden text-slate-500 md:inline">{{ $activeLabel }}</span>
                @endif
                <x-icon name="chevron-right" class="hidden size-3.5 text-slate-300 sm:inline" />
                <span class="truncate font-medium text-ink">{!! $title !!}</span>
            </nav>

            <div class="flex items-center gap-1.5">
                @if($quickCreate->isNotEmpty())
                    <div class="relative" x-data="{ o: false }" @click.outside="o = false" @keydown.escape="o = false">
                        <button type="button" class="a-btn a-btn-primary" @click="o = ! o" :aria-expanded="o.toString()"><x-icon name="plus" class="size-4" /><span class="hidden sm:inline">Create</span></button>
                        <div x-show="o" x-cloak class="a-pop absolute right-0 mt-2 w-56">
                            <p class="px-2.5 pb-1 pt-1.5 text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-400">Create new</p>
                            @foreach($quickCreate as $c)
                                <a href="{{ $c['href'] }}" class="a-menu-item"><x-icon :name="$c['icon']" class="size-4 text-slate-400" />{{ $c['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @can('leads.view')
                    <a href="{{ route('admin.leads.index', ['status' => 'new']) }}" class="a-btn a-btn-ghost a-btn-icon relative" title="{{ $newLeads }} new lead{{ $newLeads === 1 ? '' : 's' }}" aria-label="New leads">
                        <x-icon name="bell" class="size-[18px]" />
                        @if($newLeads)<span class="absolute right-1 top-1 grid min-w-4 place-items-center rounded-full bg-coral px-1 text-[0.5625rem] font-bold leading-4 text-white ring-2 ring-white">{{ $newLeads > 99 ? '99+' : $newLeads }}</span>@endif
                    </a>
                @endcan
                <a href="{{ url('/') }}" target="_blank" rel="noopener" class="a-btn a-btn-ghost a-btn-icon" title="View website" aria-label="View website"><x-icon name="globe" class="size-[18px]" /></a>
                @can('settings.manage')
                    <form method="POST" action="{{ route('admin.cache.clear') }}">@csrf
                        <button class="a-btn a-btn-ghost a-btn-icon" title="Clear website cache" aria-label="Clear website cache"><x-icon name="refresh" class="size-[18px]" /></button>
                    </form>
                @endcan

                <span class="mx-1 hidden h-5 w-px bg-line sm:block" aria-hidden="true"></span>
                <div class="relative" x-data="{ o: false }" @click.outside="o = false" @keydown.escape="o = false">
                    <button type="button" class="flex items-center gap-2 rounded-lg p-1 pr-2 transition hover:bg-mist" @click="o = ! o" :aria-expanded="o.toString()">
                        <span class="a-avatar size-7 text-[0.6875rem]">{{ $initials }}</span>
                        <x-icon name="chevron-down" class="hidden size-3.5 text-slate-400 sm:block" />
                    </button>
                    <div x-show="o" x-cloak class="a-pop absolute right-0 mt-2 w-60">
                        <div class="px-2.5 py-2">
                            <p class="truncate text-[0.8125rem] font-semibold text-ink">{{ $u->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $u->email }}</p>
                        </div>
                        <div class="a-divider"></div>
                        @can('users.manage')<a href="{{ route('admin.users.edit', $u) }}" class="a-menu-item"><x-icon name="users" class="size-4 text-slate-400" /> My profile</a>@endcan
                        <a href="{{ url('/') }}" target="_blank" rel="noopener" class="a-menu-item"><x-icon name="globe" class="size-4 text-slate-400" /> View website</a>
                        <button type="button" class="a-menu-item" @click="o = false; openPalette()"><x-icon name="command" class="size-4 text-slate-400" /> Command menu <span class="a-kbd ml-auto">Ctrl K</span></button>
                        <div class="a-divider"></div>
                        <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="a-menu-item text-red-600 hover:bg-red-50 hover:text-red-700"><x-icon name="log-out" class="size-4" /> Sign out</button></form>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
            @hasSection('page-head')
                @yield('page-head')
            @else
                <div class="a-page-head">
                    <div class="min-w-0">
                        <h1 class="a-page-title truncate">{!! $title !!}</h1>
                        @hasSection('subtitle')<p class="a-page-desc">@yield('subtitle')</p>@endif
                    </div>
                    @hasSection('actions')<div class="flex flex-wrap items-center gap-2">@yield('actions')</div>@endif
                </div>
            @endif

            @if($errors->any())
                <div class="a-alert mb-6 border-red-200 bg-red-50 text-red-800" role="alert">
                    <x-icon name="alert-triangle" class="mt-0.5 size-4 shrink-0" />
                    <div><p class="font-semibold">Please fix the following</p><ul class="mt-1 list-disc pl-5 text-[0.8125rem]">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

{{-- ================= Toasts (session flash) ================= --}}
<div class="pointer-events-none fixed right-4 top-16 z-[60] grid gap-2" aria-live="polite">
    @foreach(['success' => ['check-circle', 'text-emerald-600'], 'warning' => ['alert-triangle', 'text-amber-600'], 'error' => ['alert-triangle', 'text-red-600']] as $type => [$icon, $tone])
        @if(session($type))
            <div class="a-toast" x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, {{ $type === 'success' ? 5000 : 9000 }})" x-transition.opacity.duration.300ms role="status">
                <x-icon :name="$icon" class="mt-0.5 size-[18px] shrink-0 {{ $tone }}" />
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-ink">{{ ['success' => 'Done', 'warning' => 'Heads up', 'error' => 'Something went wrong'][$type] }}</p>
                    <p class="mt-0.5 text-[0.8125rem] text-slate-600">{{ session($type) }}</p>
                </div>
                <button type="button" @click="s = false" class="text-slate-400 hover:text-ink" aria-label="Dismiss"><x-icon name="x" class="size-4" /></button>
            </div>
        @endif
    @endforeach
</div>

{{-- ================= Command palette (Ctrl/⌘ + K) ================= --}}
<div x-show="palette" x-cloak class="fixed inset-0 z-[70] flex items-start justify-center p-4 pt-[12vh]" role="dialog" aria-modal="true" aria-label="Command menu">
    <div class="absolute inset-0 animate-fade-in bg-night/40 backdrop-blur-[2px]" @click="palette = false"></div>
    <div class="relative w-full max-w-xl animate-pop-in overflow-hidden rounded-2xl border border-line bg-white shadow-[var(--shadow-pop)]" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go()">
        <div class="flex items-center gap-3 border-b border-line px-4">
            <x-icon name="search" class="size-[18px] text-slate-400" />
            <input x-ref="paletteInput" x-model="q" @input="idx = 0" type="text" placeholder="Search pages, settings and actions…" class="h-13 flex-1 border-0 bg-transparent text-[0.9375rem] text-ink placeholder:text-slate-400 focus:outline-none focus:ring-0" aria-label="Search">
            <span class="a-kbd">Esc</span>
        </div>
        <ul class="max-h-[55vh] overflow-y-auto p-2" role="listbox">
            <template x-for="(item, i) in results" :key="item.href + item.label">
                <li role="option" :aria-selected="i === idx">
                    <a :href="item.href" @mouseenter="idx = i" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-[0.8125rem]" :class="i === idx ? 'bg-brand-50 text-brand-700' : 'text-slate-700'">
                        <span class="flex-1 font-medium" x-text="item.label"></span>
                        <span class="text-xs text-slate-400" x-text="item.group"></span>
                        <x-icon name="corner-down-left" class="size-3.5 text-brand" x-show="i === idx" />
                    </a>
                </li>
            </template>
            <li x-show="! results.length" class="px-3 py-10 text-center text-sm text-slate-500">No matches for “<span x-text="q"></span>”</li>
        </ul>
        <div class="flex items-center gap-4 border-t border-line bg-mist/60 px-4 py-2 text-[0.6875rem] text-slate-500">
            <span class="flex items-center gap-1"><span class="a-kbd">↑</span><span class="a-kbd">↓</span> navigate</span>
            <span class="flex items-center gap-1"><span class="a-kbd">↵</span> open</span>
            <span class="flex items-center gap-1"><span class="a-kbd">Esc</span> close</span>
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
