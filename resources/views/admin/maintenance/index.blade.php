@extends('admin.layouts.app')

@section('title', 'Maintenance')

@section('subtitle', 'Run deploy commands and check the server setup — the same as running php artisan over SSH.')

@section('content')
@php $status = session('frontend_status'); @endphp

@if(session('maintenance_output'))
    <section class="a-card mb-5">
        <div class="a-card-h"><div><p class="a-card-t">Last command output</p></div></div>
        <pre class="max-h-80 overflow-auto rounded-b-xl bg-ink p-4 text-xs leading-relaxed text-emerald-300">{{ session('maintenance_output') }}</pre>
    </section>
@endif

<div class="grid gap-5 xl:grid-cols-[1fr_380px]">
    <div class="grid content-start gap-5">
        <section class="a-card">
            <div class="a-card-h"><div><p class="a-card-t">Backend (Laravel)</p><p class="a-card-d">After a deploy: run migrations if any are pending, then <strong>Clear &amp; rebuild caches</strong>.</p></div></div>
            <div class="a-card-b divide-y divide-line !py-0">
                @foreach($actions as $a)
                    <form method="POST" action="{{ route('admin.maintenance.run', $a['key']) }}" class="flex flex-wrap items-center justify-between gap-3 py-3.5"
                          x-data="{ busy: false }"
                          @submit="if (@js($a['confirm']) && ! confirm(@js($a['label'].'? Make sure you have a database backup.'))) { $event.preventDefault(); return } busy = true">
                        @csrf
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink">{{ $a['label'] }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $a['help'] }}</p>
                        </div>
                        <button class="a-btn {{ $a['key'] === 'refresh' ? 'a-btn-primary' : ($a['confirm'] ? 'a-btn-danger' : 'a-btn-light') }} a-btn-sm" :disabled="busy">
                            <x-icon name="{{ $a['confirm'] ? 'database' : 'refresh' }}" class="size-4" />
                            <span x-text="busy ? 'Running…' : 'Run'">Run</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </section>

        <section class="a-card">
            <div class="a-card-h"><div><p class="a-card-t">Frontend (Next.js)</p><p class="a-card-d">The public site at {{ config('services.frontend.url') ?: '— (FRONTEND_URL not set)' }}. Code or <code>.env</code> changes there need a rebuild on its server (<code>npm run build</code>, then restart).</p></div></div>
            <div class="a-card-b divide-y divide-line !py-0">
                <form method="POST" action="{{ route('admin.maintenance.frontend', 'revalidate') }}" class="flex flex-wrap items-center justify-between gap-3 py-3.5">
                    @csrf
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-ink">Refresh website cache</p>
                        <p class="mt-0.5 text-xs text-slate-500">Drops every cached page so the next visit renders fresh content. Saving in the admin already does this automatically.</p>
                    </div>
                    <button class="a-btn a-btn-light a-btn-sm"><x-icon name="refresh" class="size-4" /> Run</button>
                </form>
                <form method="POST" action="{{ route('admin.maintenance.frontend', 'status') }}" class="flex flex-wrap items-center justify-between gap-3 py-3.5">
                    @csrf
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-ink">Check frontend setup</p>
                        <p class="mt-0.5 text-xs text-slate-500">Shows which URLs the live build uses and whether it can reach this backend.</p>
                    </div>
                    <button class="a-btn a-btn-light a-btn-sm"><x-icon name="activity" class="size-4" /> Check</button>
                </form>
            </div>

            @if($status)
                @php
                    $d = $status['data'];
                    $isLocal = fn ($u) => (bool) preg_match('#^https?://(localhost|127\.|0\.0\.0\.0)#i', (string) $u);
                    $rows = $status['ok'] ? [
                        ['SITE_URL', $d['site_url'] ?? '—', ! $isLocal($d['site_url'] ?? '') && rtrim((string) ($d['site_url'] ?? ''), '/') === rtrim((string) config('services.frontend.url'), '/'), 'should equal FRONTEND_URL'],
                        ['BACKEND_URL', $d['backend_url'] ?? '—', ! $isLocal($d['backend_url'] ?? '') || ! app()->isProduction(), 'where the Next server reaches Laravel'],
                        ['Proxy target (baked at build)', $d['proxy_target'] ?? 'n/a (dev server)', ! isset($d['proxy_target']) || $d['proxy_target'] === rtrim((string) ($d['backend_url'] ?? ''), '/'), '/api, /storage, /admin go here; must match BACKEND_URL — rebuild if not'],
                        ['Backend reachable', ($d['backend']['ok'] ?? false) ? 'yes ('.($d['backend']['ms'] ?? '?').' ms)' : 'no — '.($d['backend']['error'] ?? 'HTTP '.($d['backend']['status'] ?? '?')), (bool) ($d['backend']['ok'] ?? false), 'GET BACKEND_URL/up'],
                        ['Admin path', $d['admin_path'] ?? '—', ($d['admin_path'] ?? null) === config('site.admin_path'), 'must equal ADMIN_PATH here'],
                        ['Build', ($d['build_id'] ?? '—').(isset($d['built_at']) ? ' · '.\Illuminate\Support\Carbon::parse($d['built_at'])->diffForHumans() : ''), true, ($d['environment'] ?? '').' · Node '.($d['node'] ?? '?')],
                    ] : [];
                @endphp
                <div class="border-t border-line p-4">
                    @if(! $status['ok'])
                        <div class="a-alert border-red-200 bg-red-50 text-sm text-red-700">{{ $status['error'] }}</div>
                    @else
                        <table class="a-table">
                            <tbody>
                            @foreach($rows as [$label, $value, $ok, $hint])
                                <tr>
                                    <td class="w-6"><x-icon :name="$ok ? 'check-circle' : 'alert-triangle'" class="size-4 {{ $ok ? 'text-emerald-600' : 'text-red-600' }}" /></td>
                                    <td class="whitespace-nowrap text-sm font-medium text-ink">{{ $label }}</td>
                                    <td class="text-sm"><code class="break-all">{{ $value }}</code><span class="block text-xs text-slate-500">{{ $hint }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif
        </section>
    </div>

    <div class="grid content-start gap-5">
        <section class="a-card">
            <div class="a-card-h"><div><p class="a-card-t">Server checks</p></div></div>
            <ul class="a-card-b grid gap-3" role="list">
                @foreach($checks as $c)
                    <li class="flex gap-3">
                        <x-icon :name="$c['ok'] ? 'check-circle' : 'alert-triangle'" class="mt-0.5 size-4 shrink-0 {{ $c['ok'] ? 'text-emerald-600' : 'text-amber-600' }}" />
                        <div class="min-w-0"><p class="text-sm font-medium text-ink">{{ $c['label'] }}</p><p class="break-words text-xs text-slate-500">{{ $c['detail'] }}</p></div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="a-card">
            <div class="a-card-h"><div><p class="a-card-t">Environment</p></div></div>
            <dl class="a-card-b grid gap-2 text-sm">
                @foreach($info as $k => $v)
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ $k }}</dt><dd class="truncate font-medium text-ink" title="{{ $v }}">{{ $v }}</dd></div>
                @endforeach
            </dl>
        </section>
    </div>
</div>
@endsection
