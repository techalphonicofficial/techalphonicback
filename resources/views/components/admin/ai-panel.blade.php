@props(['page'])
@php
    $settings = app(\App\Ai\AiManager::class)->settings();
    $ready = $settings->ready();
    $providerLabel = \App\Ai\AiSettings::PROVIDERS[$settings->provider()]['label'];
    $fieldLabels = collect($settings->fields())->map(fn ($f) => \App\Ai\AiSettings::FIELDS[$f])->implode(', ');
    $live = in_array($page->status, [\App\Enums\ContentStatus::Published, \App\Enums\ContentStatus::Scheduled], true);
    $last = $page->exists ? $page->latestAiGeneration : null;
    $state = $last ? ['id' => $last->id, 'status' => $last->status, 'label' => $last->statusLabel(), 'pending' => $last->isPending(), 'error' => $last->error,
        'waiting_seconds' => $last->status === 'queued' ? (int) $last->created_at->diffInSeconds(now()) : 0] : ['status' => null, 'pending' => false];
@endphp
@can('ai.generate')
<div class="a-card overflow-hidden">
    <div class="a-card-h">
        <p class="a-card-t flex items-center gap-2"><span class="grid size-6 place-items-center rounded-md bg-gradient-to-br from-brand to-teal text-white"><x-icon name="sparkles" class="size-3.5" /></span> Write with AI</p>
        @if($ready)<span class="text-[0.6875rem] font-medium text-slate-500">{{ \Illuminate\Support\Str::before($providerLabel, ' (') }}</span>@endif
    </div>

    @if(! $page->exists)
        <div class="a-card-b grid gap-2">
            @if($ready)
                <label class="flex cursor-pointer items-start gap-2.5 text-[0.8125rem] text-slate-700">
                    <input type="checkbox" name="ai_generate" value="1" class="a-check mt-0.5" @checked(old('ai_generate'))>
                    <span><span class="font-medium text-ink">Write the content with AI after creating</span><span class="mt-0.5 block text-xs text-slate-500">{{ $fieldLabels }}.</span></span>
                </label>
            @else
                <p class="text-xs text-slate-500">AI writing isn't set up yet.@can('ai.configure') <a href="{{ route('admin.ai.settings') }}" class="font-semibold text-brand">Configure AI</a>@endcan</p>
            @endif
        </div>
    @else
        <div class="a-card-b grid gap-3" x-data="aiStatus({ url: @js(route('admin.landing-pages.ai-status', $page->id)), initial: @js($state) })">
            {{-- Live status --}}
            <template x-if="s.pending">
                <div class="rounded-lg border border-brand/20 bg-brand-50/60 p-3">
                    <p class="flex items-center gap-2 text-[0.8125rem] font-semibold text-brand-700"><x-icon name="refresh" class="size-4 animate-spin" /> <span x-text="s.status === 'running' ? 'Writing this page…' : 'Queued…'"></span></p>
                    <p class="mt-1 text-xs text-slate-600">This usually takes 1–3 minutes. The page reloads when the draft is ready.</p>
                    <p x-show="s.status === 'queued' && s.waiting_seconds > 45" class="mt-2 text-xs text-amber-700">Still queued. Is a queue worker running? <code class="rounded bg-white px-1">php artisan queue:work --timeout=900</code></p>
                </div>
            </template>
            <template x-if="!s.pending && s.status === 'failed'">
                <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800"><p class="font-semibold">Last run failed</p><p class="mt-0.5" x-text="s.error"></p></div>
            </template>

            @if($last?->status === 'succeeded')
                <div x-show="!s.pending" class="text-xs text-slate-500">
                    Last written {{ $last->finished_at?->diffForHumans() }} · <span class="font-mono">{{ $last->model }}</span>
                    @if($last->similarity !== null && $last->similarity >= 0.2)
                        <p class="mt-1.5 flex items-start gap-1.5 font-medium {{ $last->similarity >= 0.35 ? 'text-red-600' : 'text-amber-700' }}"><x-icon name="alert-triangle" class="mt-px size-3.5 shrink-0" /> {{ round($last->similarity * 100) }}% overlap with another page for this service. Rewrite or add local detail before publishing.</p>
                    @endif
                </div>
            @endif

            @if(! $ready)
                <p class="text-xs text-slate-500">AI writing isn't set up yet.@can('ai.configure') <a href="{{ route('admin.ai.settings') }}" class="font-semibold text-brand">Configure AI</a>@endcan</p>
            @elseif($live)
                <p class="text-xs text-slate-500">AI only writes to drafts. Unpublish this page to rewrite it with AI.</p>
            @else
                <button form="ai-generate" class="a-btn a-btn-dark w-full" :disabled="s.pending"
                        onclick="return confirm({{ \Illuminate\Support\Js::from('AI will replace: '.$fieldLabels.".\n\nUnsaved changes on this screen will be lost. Continue?") }})">
                    <x-icon name="sparkles" class="size-4" /> {{ $last ? 'Rewrite with AI' : 'Write with AI' }}
                </button>
                <p class="text-[0.6875rem] leading-relaxed text-slate-500">Writes {{ \Illuminate\Support\Str::lower($fieldLabels) }} into this draft. The page stays noindex until you switch indexing on.</p>
            @endif
        </div>
    @endif
</div>
@endcan
