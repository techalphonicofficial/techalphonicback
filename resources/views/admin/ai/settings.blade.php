@extends('admin.layouts.app')

@section('title', 'AI content')

@section('subtitle', 'Choose the AI provider and model that writes programmatic SEO pages. Content is applied to drafts only, and pages stay noindex until someone reviews and publishes them.')

@section('actions')
    @can('ai.generate')<a href="{{ route('admin.ai.generations') }}" class="a-btn a-btn-light"><x-icon name="activity" class="size-4" /> Writing log</a>@endcan
@endsection

@section('content')
@php
    $active = old('ai_provider', $s->provider());
    $fields = old('ai_fields', $s->fields());
@endphp
<form method="POST" action="{{ route('admin.ai.settings.update') }}" class="grid gap-6 xl:grid-cols-[1fr_340px]" x-data="{ provider: @js($active) }" data-guard>
    @csrf @method('PUT')

    <div class="grid min-w-0 content-start gap-6">
        {{-- Providers --}}
        <section class="a-card">
            <div class="a-card-h">
                <div><p class="a-card-t">Provider & model</p><p class="a-card-d">Keys are encrypted before they're stored and are never shown again. Leave a key field blank to keep the saved key.</p></div>
            </div>
            <div class="divide-y divide-line">
                @foreach($providers as $key => $p)
                    <div class="px-5 py-4 transition-colors" :class="provider === '{{ $key }}' && 'bg-brand-50/40'"
                         x-data="aiProvider({ provider: @js($key), model: @js(old("ai_{$key}_model", $s->get("ai_{$key}_model"))), models: @js($p['models']), testUrl: @js(route('admin.ai.settings.test')), modelsUrl: @js(route('admin.ai.settings.models')) })">
                        <label class="flex cursor-pointer flex-wrap items-center gap-3">
                            <input type="radio" name="ai_provider" value="{{ $key }}" x-model="provider" class="size-4 border-line-strong text-brand focus:ring-brand/30">
                            <span class="font-semibold text-ink">{{ $p['label'] }}</span>
                            @if($masked[$key])
                                <span class="a-badge bg-emerald-50 text-emerald-700"><span class="a-dot"></span> Key saved <span class="font-mono font-medium">{{ $masked[$key] }}</span></span>
                            @else
                                <span class="a-badge bg-slate-100 text-slate-500">No key</span>
                            @endif
                            <span x-show="provider === '{{ $key }}'" class="a-badge bg-brand text-white ring-0">In use</span>
                        </label>

                        <div class="mt-4 grid gap-4 pl-7 md:grid-cols-2">
                            <div>
                                <label class="a-label" for="m_{{ $key }}">Model</label>
                                <input id="m_{{ $key }}" name="ai_{{ $key }}_model" x-model="model" list="models_{{ $key }}" class="a-input font-mono text-[0.8125rem]" placeholder="{{ $p['default_model'] }}" autocomplete="off" spellcheck="false">
                                <datalist id="models_{{ $key }}"><template x-for="(label, id) in models" :key="id"><option :value="id" x-text="label"></option></template></datalist>
                                <p class="a-help">Pick a suggestion or type any model ID your account can use. Blank = <span class="font-mono">{{ $p['default_model'] }}</span>.</p>
                                @error("ai_{$key}_model")<p class="a-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="a-label" for="k_{{ $key }}">API key</label>
                                <input id="k_{{ $key }}" type="password" name="ai_{{ $key }}_key" x-model="key" class="a-input font-mono text-[0.8125rem]" autocomplete="new-password" spellcheck="false"
                                       placeholder="{{ $masked[$key] ? 'Saved: leave blank to keep' : 'Paste your API key' }}">
                                <p class="a-help flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <a href="{{ $p['keys_url'] }}" target="_blank" rel="noopener noreferrer" class="font-medium text-brand hover:underline">Get a key <x-icon name="arrow-up-right" class="inline size-3" /></a>
                                    @if($masked[$key])
                                        <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="ai_{{ $key }}_key_remove" value="1" class="a-check size-3.5"> Remove saved key</label>
                                    @endif
                                </p>
                                @error("ai_{$key}_key")<p class="a-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex flex-wrap items-center gap-2 md:col-span-2">
                                <button type="button" class="a-btn a-btn-light a-btn-sm" @click="test()" :disabled="busy !== ''">
                                    <x-icon name="refresh" class="size-3.5" x-bind:class="busy === 'test' && 'animate-spin'" /> Test connection
                                </button>
                                <button type="button" class="a-btn a-btn-ghost a-btn-sm" @click="fetchModels()" :disabled="busy !== ''">
                                    <x-icon name="database" class="size-3.5" x-bind:class="busy === 'models' && 'animate-pulse'" /> Load models from {{ \Illuminate\Support\Str::before($p['label'], ' (') }}
                                </button>
                                <p x-show="result" x-cloak class="flex items-center gap-1.5 text-xs font-medium" :class="result?.ok ? 'text-emerald-700' : 'text-red-600'" role="status">
                                    <span class="a-dot"></span><span x-text="result?.message"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- What to write --}}
        <section class="a-card">
            <div class="a-card-h"><div><p class="a-card-t">What AI writes</p><p class="a-card-d">These parts of a page are replaced each time AI runs. Everything else is left alone.</p></div></div>
            <div class="a-card-b grid gap-5">
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach(\App\Ai\AiSettings::FIELDS as $k => $label)
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-line px-3 py-2.5 text-[0.8125rem] font-medium text-slate-700 transition has-[:checked]:border-brand/40 has-[:checked]:bg-brand-50/50 has-[:checked]:text-ink">
                            <input type="checkbox" name="ai_fields[]" value="{{ $k }}" class="a-check" @checked(in_array($k, $fields, true))> {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('ai_fields')<p class="a-error">{{ $message }}</p>@enderror
                <p class="text-xs text-slate-500">AI-written FAQs are saved directly with the page. Running AI again replaces the FAQs it wrote before; FAQs you attached by hand stay.</p>
                <x-admin.field type="textarea" name="ai_brand_voice" label="Brand voice" :value="$s->get('ai_brand_voice')" rows="2" help="How pages should sound, e.g. “Friendly and practical, Indian English, no hype.”" />
                <x-admin.field type="textarea" name="ai_instructions" label="Extra instructions (optional)" :value="$s->get('ai_instructions')" rows="4" help="Facts AI may use (certifications, guarantees you really offer) or rules to follow. AI won't invent facts that aren't given here or on the service, location and industry records." />
            </div>
        </section>

        {{-- Tuning --}}
        <section class="a-card">
            <div class="a-card-h"><div><p class="a-card-t">Generation</p><p class="a-card-d">Defaults suit most sites.</p></div></div>
            <div class="a-card-b grid gap-4 md:grid-cols-3">
                <x-admin.field type="select" name="ai_effort" label="Effort" :value="$s->effort()" :options="\App\Ai\AiSettings::EFFORTS" :empty="false" help="Used by Claude and OpenAI reasoning models." />
                <x-admin.field type="number" name="ai_temperature" label="Temperature" :value="$s->get('ai_temperature')" step="0.1" min="0" max="1.5" help="Used by Gemini and non-reasoning OpenAI models." />
                <x-admin.field type="number" name="ai_max_tokens" label="Max output tokens" :value="$s->get('ai_max_tokens')" step="1000" min="2000" max="64000" help="Raise this if runs fail with a token-limit error." />
            </div>
        </section>

        {{-- Budget --}}
        <section class="a-card">
            <div class="a-card-h"><div><p class="a-card-t">Budget</p><p class="a-card-d">Enter the model's current prices (from the provider's pricing page) to track spend. New runs stop once the monthly budget is reached.</p></div></div>
            <div class="a-card-b grid gap-4 md:grid-cols-3">
                <x-admin.field type="number" name="ai_price_input" label="Input price (USD / 1M tokens)" :value="$s->get('ai_price_input')" step="0.01" min="0" />
                <x-admin.field type="number" name="ai_price_output" label="Output price (USD / 1M tokens)" :value="$s->get('ai_price_output')" step="0.01" min="0" />
                <x-admin.field type="number" name="ai_monthly_budget" label="Monthly budget (USD)" :value="$s->get('ai_monthly_budget')" step="1" min="0" help="Blank = no limit." />
            </div>
        </section>
    </div>

    {{-- Sidebar --}}
    <aside class="grid content-start gap-4 xl:sticky xl:top-20 xl:self-start">
        <div class="a-card">
            <div class="a-card-h"><p class="a-card-t">Status</p>
                @if($s->ready())<span class="a-badge bg-emerald-50 text-emerald-700"><span class="a-dot"></span> Ready</span>
                @elseif($s->enabled())<span class="a-badge bg-amber-50 text-amber-700"><span class="a-dot"></span> Needs a key</span>
                @else<span class="a-badge bg-slate-100 text-slate-600"><span class="a-dot"></span> Off</span>@endif
            </div>
            <div class="a-card-b grid gap-4">
                <x-admin.field type="checkbox" name="ai_enabled" label="Enable AI writing" :value="$s->enabled()" help="Shows “Write with AI” on SEO pages, the bulk generator and bulk actions." />
                <button type="submit" class="a-btn a-btn-primary w-full"><x-icon name="check" class="size-4" /> Save settings</button>
            </div>
        </div>

        <div class="a-card">
            <div class="a-card-h"><p class="a-card-t">This month</p><span class="text-xs text-slate-500">{{ now()->format('F Y') }}</span></div>
            <div class="a-card-b grid gap-4">
                <dl class="grid grid-cols-2 gap-3">
                    <div><dt class="text-xs text-slate-500">Pages written</dt><dd class="text-lg font-semibold text-ink">{{ number_format($usage['succeeded']) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Failed</dt><dd class="text-lg font-semibold {{ $usage['failed'] ? 'text-red-600' : 'text-ink' }}">{{ number_format($usage['failed']) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Tokens</dt><dd class="text-lg font-semibold text-ink">{{ \App\Models\AiGeneration::compact($usage['tokens']) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Est. cost</dt><dd class="text-lg font-semibold text-ink">{{ $usage['priced'] || $usage['cost'] ? '$'.number_format($usage['cost'], 2) : '—' }}</dd></div>
                </dl>
                @if($usage['budget'])
                    @php $pct = min(100, round($usage['cost'] / $usage['budget'] * 100)); @endphp
                    <div>
                        <div class="mb-1 flex justify-between text-xs text-slate-500"><span>Budget</span><span>${{ number_format($usage['cost'], 2) }} of ${{ number_format($usage['budget'], 2) }}</span></div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-mist"><div class="h-full rounded-full {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-amber' : 'bg-brand') }}" style="width: {{ $pct }}%"></div></div>
                    </div>
                @endif
                @if($usage['pending'])
                    <p class="flex items-center gap-2 text-xs text-slate-600"><span class="a-dot animate-pulse text-brand"></span> {{ $usage['pending'] }} {{ \Illuminate\Support\Str::plural('page', $usage['pending']) }} in progress</p>
                @endif
            </div>
        </div>

        @if($queue === 'sync')
            <div class="a-alert border-amber/40 bg-amber/10 text-ink"><x-icon name="alert-triangle" class="mt-0.5 size-4 shrink-0 text-amber-600" /><p class="text-xs">The queue runs synchronously (<code>QUEUE_CONNECTION=sync</code>), so each page is written while the browser waits. Use the database queue with a worker for bulk runs.</p></div>
        @else
            <div class="a-alert border-line bg-white text-slate-600"><x-icon name="info" class="mt-0.5 size-4 shrink-0 text-slate-400" /><p class="text-xs">Pages are written in the background. A queue worker must be running: <code class="rounded bg-mist px-1">php artisan queue:work --timeout=900</code></p></div>
        @endif

        @if($recent->isNotEmpty())
            <div class="a-card">
                <div class="a-card-h"><p class="a-card-t">Recent runs</p>@can('ai.generate')<a href="{{ route('admin.ai.generations') }}" class="text-xs font-semibold text-brand">View all</a>@endcan</div>
                <ul class="divide-y divide-line">
                    @foreach($recent as $g)
                        <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-[0.8125rem]">
                            <span class="min-w-0 truncate text-slate-700">{{ $g->subject?->title ?? 'Deleted page' }}</span>
                            <span class="a-badge shrink-0 {{ $g->statusClasses() }}">{{ $g->statusLabel() }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </aside>
</form>
@endsection
