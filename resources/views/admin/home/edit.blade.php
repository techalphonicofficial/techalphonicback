@extends('admin.layouts.app')

@section('title', 'Homepage')

@section('content')
<div class="grid gap-6">
    {{-- HERO --}}
    <form method="POST" action="{{ route('admin.home.hero') }}" class="a-card" id="hero">
        @csrf @method('PUT')
        <div class="a-card-h"><p class="a-card-t">Hero section</p><a href="{{ url('/') }}" target="_blank" class="text-xs font-semibold text-brand">View homepage ↗</a></div>
        <div class="a-card-b grid gap-4 md:grid-cols-2">
            <x-admin.field name="badge" label="Badge" :value="$hero->badge" placeholder="e.g. Trusted by 300+ businesses" />
            <x-admin.field name="subheading" label="Subheading" :value="$hero->subheading" />
            <x-admin.field name="heading" label="Heading (H1)" :value="$hero->heading" required class="md:col-span-2" help="Wrap words in [[double brackets]] to give them the brand gradient." />
            <x-admin.field type="textarea" name="description" label="Description" :value="$hero->description" rows="3" class="md:col-span-2" />
            <x-admin.field name="cta_text" label="Primary CTA text" :value="$hero->cta_text" />
            <x-admin.field name="cta_url" label="Primary CTA URL" :value="$hero->cta_url" placeholder="#enquiry-popup" help="Leave as #enquiry-popup to open the enquiry popup." />
            <x-admin.field name="secondary_cta_text" label="Video button text" :value="$hero->secondary_cta_text" placeholder="Watch how we work" />
            <x-admin.field name="video_url" label="“Watch video” URL (YouTube or MP4)" :value="$hero->video_url" help="Empty = plays the background video in the popup player." />
            <x-admin.field name="trust_text" label="Trust text" :value="$hero->trust_text" placeholder="4.9/5 from 120+ client reviews" class="md:col-span-2" />
            <x-admin.media-picker name="image_id" label="Hero image (right side, if form is off)" :media="$hero->image" />
            <x-admin.media-picker name="background_id" label="Banner image / video poster" :media="$hero->background" help="Shown instantly while the video loads (and on reduced-motion devices)." />
            <x-admin.media-picker name="background_video_id" label="Background video (MP4, muted loop)" type="video" :media="$hero->backgroundVideo" help="Keep it short (10–20 s) and under ~8 MB. Upload MP4 in Media Library." />
            <x-admin.field type="checkbox" name="show_form" label="Show enquiry form in hero" :value="$hero->show_form" />
            <x-admin.field type="checkbox" name="is_active" label="Hero active" :value="$hero->is_active" />
        </div>
        <div class="flex justify-end border-t border-line p-4"><button class="a-btn a-btn-primary">Save hero</button></div>
    </form>

    {{-- SECTIONS --}}
    <form method="POST" action="{{ route('admin.home.sections') }}" class="a-card" x-data="{ open: null }">
        @csrf @method('PUT')
        <div class="a-card-h"><p class="a-card-t">Sections — drag to reorder, toggle visibility</p><button class="a-btn a-btn-primary a-btn-sm">Save sections</button></div>
        <ul class="divide-y divide-line" x-data="orderInputs">
            @foreach($sections as $s)
                <li class="p-4" data-id="{{ $s->id }}" x-data="{ c: @js($s->content ?? new stdClass) }">
                    <input type="hidden" name="sections[{{ $s->key }}][sort_order]" value="{{ $s->sort_order }}" data-order>
                    <input type="hidden" name="sections[{{ $s->key }}][content]" :value="JSON.stringify(c)">
                    <div class="flex items-center gap-3">
                        <span data-handle class="cursor-grab text-slate-400"><x-icon name="menu" class="size-4" /></span>
                        <button type="button" class="flex-1 text-left font-semibold text-ink" @click="open = open === '{{ $s->key }}' ? null : '{{ $s->key }}'">{{ \App\Models\HomeSection::KEYS[$s->key] ?? $s->key }}</button>
                        <label class="flex items-center gap-1.5 text-xs text-slate-600"><input type="hidden" name="sections[{{ $s->key }}][is_visible]" value="0"><input type="checkbox" name="sections[{{ $s->key }}][is_visible]" value="1" class="a-check" @checked($s->is_visible)> Visible</label>
                    </div>
                    <div x-show="open === '{{ $s->key }}'" x-collapse class="mt-4 grid gap-3 md:grid-cols-3">
                        @unless($s->key === 'hero')
                            <x-admin.field :name="'sections['.$s->key.'][eyebrow]'" label="Eyebrow" :value="$s->eyebrow" />
                            <x-admin.field :name="'sections['.$s->key.'][title]'" label="Title" :value="$s->title" class="md:col-span-2" />
                            <x-admin.field :name="'sections['.$s->key.'][subtitle]'" label="Subtitle" :value="$s->subtitle" class="md:col-span-3" />
                        @endunless

                        @switch($s->key)
                            @case('hero')
                                <div class="md:col-span-3">
                                    <p class="a-label">Stats bar under the hero</p>
                                    <template x-for="(row, i) in (c.stats ||= [])" :key="i">
                                        <div class="mb-2 grid grid-cols-[1fr_80px_2fr_auto] gap-2"><input class="a-input" x-model="row.value" placeholder="250"><input class="a-input" x-model="row.suffix" placeholder="+"><input class="a-input" x-model="row.label" placeholder="Projects delivered"><button type="button" class="text-red-500" @click="c.stats.splice(i, 1)">✕</button></div>
                                    </template>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="(c.stats ||= []).push({})">+ Add stat</button>
                                </div>
                                <div class="md:col-span-3"><label class="a-label">Moving ribbon under the hero (one item per line)</label>
                                    <textarea rows="4" class="a-input" :value="(c.ticker || []).join('\n')" @input="c.ticker = $event.target.value.split('\n').filter(Boolean)"></textarea></div>
                                @break
                            @case('services')
                                <div><label class="a-label">Feature tile image (media ID)</label><input type="number" class="a-input" x-model.number="c.image_id"></div>
                                @break
                            @case('promo')
                                <div><label class="a-label">Image (media ID)</label><input type="number" class="a-input" x-model.number="c.image_id"></div>
                                <div><label class="a-label">Button text</label><input class="a-input" x-model="c.button_text" placeholder="Claim my free audit"></div>
                                <div><label class="a-label">Small note</label><input class="a-input" x-model="c.note" placeholder="Delivered within 48 hours"></div>
                                <div><label class="a-label">Price label</label><input class="a-input" x-model="c.price_label" placeholder="Audit value"></div>
                                <div><label class="a-label">Old price (strikethrough)</label><input class="a-input" x-model="c.price_old" placeholder="₹9,999 — leave empty to hide"></div>
                                <div><label class="a-label">New price</label><input class="a-input" x-model="c.price_new" placeholder="FREE"></div>
                                <div class="md:col-span-3"><label class="a-label">Points (one per line)</label>
                                    <textarea rows="3" class="a-input" :value="(c.points || []).join('\n')" @input="c.points = $event.target.value.split('\n').filter(Boolean)"></textarea></div>
                                <p class="text-xs text-slate-500 md:col-span-3">The button opens the enquiry popup.</p>
                                @break
                            @case('showreel')
                                <div><label class="a-label">Cover image (media ID)</label><input type="number" class="a-input" x-model.number="c.image_id"></div>
                                <div><label class="a-label">Video (media ID of an MP4)</label><input type="number" class="a-input" x-model.number="c.video_id"></div>
                                <div><label class="a-label">…or YouTube / MP4 URL</label><input class="a-input" x-model="c.video_url" placeholder="https://youtu.be/…"></div>
                                <div class="md:col-span-3">
                                    <p class="a-label">Stat chips</p>
                                    <template x-for="(row, i) in (c.stats ||= [])" :key="i">
                                        <div class="mb-2 grid grid-cols-[1fr_80px_2fr_auto] gap-2"><input class="a-input" x-model="row.value" placeholder="3.2"><input class="a-input" x-model="row.suffix" placeholder="x"><input class="a-input" x-model="row.label" placeholder="Average lead growth"><button type="button" class="text-red-500" @click="c.stats.splice(i, 1)">✕</button></div>
                                    </template>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="(c.stats ||= []).push({})">+ Add stat</button>
                                </div>
                                <p class="text-xs text-slate-500 md:col-span-3">Find media IDs in <a href="{{ route('admin.media.index') }}" target="_blank" class="text-brand">Media Library ↗</a>.</p>
                                @break
                            @case('stats')
                                <div class="md:col-span-3">
                                    <template x-for="(row, i) in (c.items ||= [])" :key="i">
                                        <div class="mb-2 grid grid-cols-[1fr_80px_2fr_auto] gap-2"><input class="a-input" x-model="row.value" placeholder="98"><input class="a-input" x-model="row.suffix" placeholder="%"><input class="a-input" x-model="row.label" placeholder="Client retention"><button type="button" class="text-red-500" @click="c.items.splice(i, 1)">✕</button></div>
                                    </template>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="(c.items ||= []).push({})">+ Add stat</button>
                                </div>
                                @break
                            @case('about')
                                <div class="md:col-span-3"><label class="a-label">Text (HTML)</label><textarea rows="5" class="a-input" x-model="c.text"></textarea></div>
                                <div><label class="a-label">Image media ID</label><input type="number" class="a-input" x-model.number="c.image_id"></div>
                                <div><label class="a-label">CTA text</label><input class="a-input" x-model="c.cta_text"></div>
                                <div><label class="a-label">CTA URL</label><input class="a-input" x-model="c.cta_url"></div>
                                <div><label class="a-label">Badge value</label><input class="a-input" x-model="c.badge_value" placeholder="10"></div>
                                <div><label class="a-label">Badge suffix</label><input class="a-input" x-model="c.badge_suffix" placeholder="+"></div>
                                <div><label class="a-label">Badge label</label><input class="a-input" x-model="c.badge_label" placeholder="Years of experience"></div>
                                <div class="md:col-span-3"><label class="a-label">Bullet points (one per line)</label>
                                    <textarea rows="4" class="a-input" :value="(c.points || []).join('\n')" @input="c.points = $event.target.value.split('\n').filter(Boolean)"></textarea></div>
                                @break
                            @case('process')
                                <div class="md:col-span-3">
                                    <template x-for="(row, i) in (c.steps ||= [])" :key="i">
                                        <div class="mb-2 grid grid-cols-[1fr_2fr_140px_auto] gap-2"><input class="a-input" x-model="row.title" placeholder="Step title"><input class="a-input" x-model="row.text" placeholder="Description">
                                            <select class="a-input" x-model="row.icon">@foreach($icons as $ic)<option value="{{ $ic }}">{{ $ic }}</option>@endforeach</select>
                                            <button type="button" class="text-red-500" @click="c.steps.splice(i, 1)">✕</button></div>
                                    </template>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="(c.steps ||= []).push({ icon: 'sparkles' })">+ Add step</button>
                                </div>
                                @break
                            @case('technologies')
                                <div class="md:col-span-3">
                                    <template x-for="(row, i) in (c.items ||= [])" :key="i">
                                        <div class="mb-2 grid grid-cols-[1fr_1fr_auto] gap-2"><input class="a-input" x-model="row.name" placeholder="Laravel"><input class="a-input" x-model="row.group" placeholder="Group, e.g. Backend"><button type="button" class="text-red-500" @click="c.items.splice(i, 1)">✕</button></div>
                                    </template>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="(c.items ||= []).push({})">+ Add technology</button>
                                </div>
                                @break
                            @case('clients')
                                <div class="md:col-span-3" x-data="{
                                    thumbs: @js($mediaThumbs ?? (object) []),
                                    pickerOpen: false,
                                    activeIdx: null,
                                    library: [],
                                    nextUrl: null,
                                    q: '',
                                    loading: false,
                                    uploading: false,
                                    async loadLibrary(reset = true) {
                                        this.loading = true;
                                        const base = @js(route('admin.media.index'));
                                        const url = reset ? `${base}?type=image&q=${encodeURIComponent(this.q)}` : this.nextUrl;
                                        if (!url) { this.loading = false; return; }
                                        const res = await fetch(url, { headers: { Accept: 'application/json' } });
                                        const json = await res.json();
                                        this.library = reset ? json.data : [...this.library, ...json.data];
                                        this.nextUrl = json.next;
                                        this.loading = false;
                                    },
                                    openPicker(i) {
                                        this.activeIdx = i;
                                        this.pickerOpen = true;
                                        if (!this.library.length) this.loadLibrary(true);
                                    },
                                    selectMedia(m) {
                                        if (this.activeIdx !== null && c.items?.[this.activeIdx]) {
                                            c.items[this.activeIdx].logo_id = m.id;
                                        }
                                        this.thumbs[m.id] = m.thumb;
                                        this.pickerOpen = false;
                                    },
                                    async uploadLogo(e) {
                                        const files = [...e.target.files];
                                        if (!files.length) return;
                                        this.uploading = true;
                                        const fd = new FormData();
                                        files.forEach((f) => fd.append('files[]', f));
                                        const token = document.querySelector('meta[name=csrf-token]')?.content || '';
                                        const res = await fetch(@js(route('admin.media.store')), {
                                            method: 'POST',
                                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
                                            body: fd,
                                        });
                                        this.uploading = false;
                                        if (!res.ok) { alert('Upload failed — check file type and size.'); return; }
                                        const json = await res.json();
                                        this.library = [...json.data, ...this.library];
                                        if (json.data.length === 1) this.selectMedia(json.data[0]);
                                        e.target.value = '';
                                    },
                                }">
                                    <p class="a-label mb-2">Client logos & website links</p>
                                    <template x-for="(row, i) in (c.items ||= [])" :key="i">
                                        <div class="mb-2.5 grid gap-2 rounded-xl border border-line bg-mist/40 p-2.5 md:grid-cols-[auto_1.2fr_1.8fr_110px_auto] md:items-center">
                                            <div class="flex items-center gap-2">
                                                <div class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-lg border border-line bg-white">
                                                    <img x-show="row.logo_id && thumbs[row.logo_id]" :src="thumbs[row.logo_id]" :alt="row.name || 'Client logo'" class="size-full object-contain p-1">
                                                    <x-icon x-show="!(row.logo_id && thumbs[row.logo_id])" name="image" class="size-4 text-slate-300" />
                                                </div>
                                                <button type="button" class="a-btn a-btn-light a-btn-sm shrink-0" @click="openPicker(i)">Choose icon</button>
                                            </div>
                                            <input class="a-input" x-model="row.name" placeholder="Client name (e.g. AyurGrid)">
                                            <input class="a-input" x-model="row.url" placeholder="Website link (https://example.com)">
                                            <input type="number" class="a-input" x-model.number="row.logo_id" placeholder="Media ID">
                                            <button type="button" class="px-2 text-red-500 hover:text-red-700" @click="c.items.splice(i, 1)" title="Remove client">✕</button>
                                        </div>
                                    </template>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="(c.items ||= []).push({ name: '', url: '', logo_id: null })">+ Add client</button>

                                    {{-- Client Icon Media Picker Modal --}}
                                    <div x-show="pickerOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-4" @keydown.escape.window="pickerOpen = false">
                                        <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" @click.outside="pickerOpen = false">
                                            <div class="flex flex-wrap items-center gap-2 border-b border-line p-4">
                                                <p class="flex-1 font-semibold text-ink">Select client icon / logo</p>
                                                <input type="search" x-model="q" @input.debounce.350ms="loadLibrary(true)" placeholder="Search…" class="a-input w-48">
                                                <label class="a-btn a-btn-primary cursor-pointer">
                                                    <x-icon name="plus" class="size-4" /> <span x-text="uploading ? 'Uploading…' : 'Upload'"></span>
                                                    <input type="file" class="hidden" accept="image/*" @change="uploadLogo($event)">
                                                </label>
                                                <button type="button" class="a-btn a-btn-light" @click="pickerOpen = false">Close</button>
                                            </div>
                                            <div class="grid flex-1 grid-cols-3 gap-3 overflow-y-auto p-4 sm:grid-cols-5 lg:grid-cols-6">
                                                <template x-for="m in library" :key="m.id">
                                                    <button type="button" @click="selectMedia(m)" class="group flex flex-col overflow-hidden rounded-lg border-2 p-1.5 text-left transition"
                                                            :class="(activeIdx !== null && c.items?.[activeIdx]?.logo_id === m.id) ? 'border-brand ring-2 ring-brand/30' : 'border-line hover:border-brand/50'">
                                                        <img :src="m.thumb" :alt="m.alt" loading="lazy" class="aspect-video w-full object-contain">
                                                        <span class="mt-1 truncate text-[0.68rem] text-slate-600" x-text="'#' + m.id + ' ' + m.filename"></span>
                                                    </button>
                                                </template>
                                                <p x-show="!loading && !library.length" class="col-span-full py-10 text-center text-sm text-slate-500">No images found — upload a client icon.</p>
                                            </div>
                                            <div class="border-t border-line p-3 text-center" x-show="nextUrl"><button type="button" class="a-btn a-btn-light a-btn-sm" @click="loadLibrary(false)">Load more</button></div>
                                        </div>
                                    </div>
                                </div>
                                @break
                            @case('faq')
                                <div class="md:col-span-3">
                                    <label class="a-label">FAQs to show (empty = first 6 in the “General” category)</label>
                                    <select multiple size="8" class="a-input" @change="c.faq_ids = [...$event.target.selectedOptions].map(o => Number(o.value))">
                                        @foreach($faqs as $f)<option value="{{ $f->id }}" :selected="(c.faq_ids || []).includes({{ $f->id }})">{{ $f->question }}</option>@endforeach
                                    </select>
                                </div>
                                @break
                            @case('cta')
                                <div><label class="a-label">Button text</label><input class="a-input" x-model="c.button_text" placeholder="Get my free consultation"></div>
                                <div><label class="a-label">Background image (media ID)</label><input type="number" class="a-input" x-model.number="c.image_id" placeholder="Defaults to the offer banner image"></div>
                                <p class="text-xs text-slate-500 md:col-span-3">The button opens the enquiry popup.</p>
                                @break
                            @default
                                <p class="text-xs text-slate-500 md:col-span-3">Items in this section come from their own modules (Services, Industries, Testimonials, Portfolio, Blogs).</p>
                        @endswitch
                    </div>
                </li>
            @endforeach
        </ul>
    </form>
</div>
@endsection
