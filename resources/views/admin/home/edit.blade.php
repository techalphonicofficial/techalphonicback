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
            <x-admin.field name="secondary_cta_text" label="Secondary / video button text" :value="$hero->secondary_cta_text" placeholder="Watch how we work" />
            <x-admin.field name="secondary_cta_url" label="Secondary button URL (when no video)" :value="$hero->secondary_cta_url" placeholder="/portfolio/" />
            <x-admin.field name="video_url" label="“Watch video” URL (YouTube or MP4)" :value="$hero->video_url" help="Empty = plays the background video in the popup player." class="md:col-span-2" />
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
    <form method="POST" action="{{ route('admin.home.sections') }}" class="a-card" x-data="{
        open: null,
        thumbs: @js($mediaThumbs ?? (object) []),
        pickerOpen: false,
        pickerType: 'image',
        pickerCb: null,
        library: [],
        nextUrl: null,
        q: '',
        loading: false,
        uploading: false,
        async loadLibrary(reset = true) {
            this.loading = true;
            const base = @js(route('admin.media.index'));
            const url = reset ? `${base}?type=${this.pickerType}&q=${encodeURIComponent(this.q)}` : this.nextUrl;
            if (!url) { this.loading = false; return; }
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const json = await res.json();
            this.library = reset ? json.data : [...this.library, ...json.data];
            this.nextUrl = json.next;
            this.loading = false;
        },
        openSectionPicker(type, cb) {
            this.pickerType = type || 'image';
            this.pickerCb = cb;
            this.library = [];
            this.nextUrl = null;
            this.q = '';
            this.pickerOpen = true;
            this.loadLibrary(true);
        },
        selectSectionMedia(m) {
            this.thumbs[m.id] = m.thumb;
            if (typeof this.pickerCb === 'function') this.pickerCb(m);
            this.pickerOpen = false;
        },
        async uploadSectionMedia(e) {
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
            if (json.data.length === 1) this.selectSectionMedia(json.data[0]);
            e.target.value = '';
        },
    }">
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
                                <div><label class="a-label">Video button subtext</label><input class="a-input" x-model="c.video_caption" placeholder="1 min overview"></div>
                                <div><label class="a-label">Hero form badge</label><input class="a-input" x-model="c.form_badge" placeholder="Free consultation"></div>
                                <div><label class="a-label">Hero form reply pill</label><input class="a-input" x-model="c.form_reply_badge" placeholder="Reply within 24h"></div>
                                <div><label class="a-label">Hero form heading</label><input class="a-input" x-model="c.form_title" placeholder="Get your free growth plan"></div>
                                <div><label class="a-label">Hero form subtitle</label><input class="a-input" x-model="c.form_subtitle" placeholder="Tell us what you need — a specialist will call you back."></div>
                                <div><label class="a-label">Hero form button text</label><input class="a-input" x-model="c.form_button" placeholder="Get free consultation"></div>
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
                                <div class="flex items-end gap-2 md:col-span-3">
                                    <div class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-lg border border-line bg-mist">
                                        <img x-show="c.image_id && thumbs[c.image_id]" :src="thumbs[c.image_id]" class="size-full object-cover" alt="">
                                        <x-icon x-show="!(c.image_id && thumbs[c.image_id])" name="image" class="size-5 text-slate-300" />
                                    </div>
                                    <div class="w-40"><label class="a-label">Feature tile image ID</label><input type="number" class="a-input" x-model.number="c.image_id"></div>
                                    <button type="button" class="a-btn a-btn-light" @click="openSectionPicker('image', (m) => { c.image_id = m.id; })">Choose image</button>
                                </div>
                                <div><label class="a-label">Help strip title</label><input class="a-input" x-model="c.help_title" placeholder="Not sure which service you need?"></div>
                                <div><label class="a-label">Help strip subtitle</label><input class="a-input" x-model="c.help_text" placeholder="Tell us your goal — we’ll recommend the right mix, free."></div>
                                <div><label class="a-label">Help strip button text</label><input class="a-input" x-model="c.help_button_text" placeholder="Get expert advice"></div>
                                @break
                            @case('promo')
                                <div class="flex items-end gap-2">
                                    <div class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-lg border border-line bg-mist">
                                        <img x-show="c.image_id && thumbs[c.image_id]" :src="thumbs[c.image_id]" class="size-full object-cover" alt="">
                                        <x-icon x-show="!(c.image_id && thumbs[c.image_id])" name="image" class="size-4 text-slate-300" />
                                    </div>
                                    <div class="flex-1"><label class="a-label">Image (media ID)</label><input type="number" class="a-input" x-model.number="c.image_id"></div>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="openSectionPicker('image', (m) => { c.image_id = m.id; })">Choose</button>
                                </div>
                                <div><label class="a-label">Button text</label><input class="a-input" x-model="c.button_text" placeholder="Claim my free audit"></div>
                                <div><label class="a-label">Button URL (empty = opens popup)</label><input class="a-input" x-model="c.button_url" placeholder="#enquiry-popup or /packages/"></div>
                                <div><label class="a-label">Small note</label><input class="a-input" x-model="c.note" placeholder="Delivered within 48 hours"></div>
                                <div><label class="a-label">Price label</label><input class="a-input" x-model="c.price_label" placeholder="Audit value"></div>
                                <div><label class="a-label">Old price (strikethrough)</label><input class="a-input" x-model="c.price_old" placeholder="₹9,999 — leave empty to hide"></div>
                                <div><label class="a-label">New price</label><input class="a-input" x-model="c.price_new" placeholder="FREE"></div>
                                <div class="md:col-span-3"><label class="a-label">Points (one per line)</label>
                                    <textarea rows="3" class="a-input" :value="(c.points || []).join('\n')" @input="c.points = $event.target.value.split('\n').filter(Boolean)"></textarea></div>
                                @break
                            @case('showreel')
                                <div class="flex items-end gap-2">
                                    <div class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-lg border border-line bg-mist">
                                        <img x-show="c.image_id && thumbs[c.image_id]" :src="thumbs[c.image_id]" class="size-full object-cover" alt="">
                                        <x-icon x-show="!(c.image_id && thumbs[c.image_id])" name="image" class="size-4 text-slate-300" />
                                    </div>
                                    <div class="flex-1"><label class="a-label">Cover image ID</label><input type="number" class="a-input" x-model.number="c.image_id"></div>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="openSectionPicker('image', (m) => { c.image_id = m.id; })">Choose</button>
                                </div>
                                <div class="flex items-end gap-2">
                                    <div class="flex-1"><label class="a-label">Video (MP4 media ID)</label><input type="number" class="a-input" x-model.number="c.video_id"></div>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="openSectionPicker('video', (m) => { c.video_id = m.id; })">Choose MP4</button>
                                </div>
                                <div><label class="a-label">…or YouTube / MP4 URL</label><input class="a-input" x-model="c.video_url" placeholder="https://youtu.be/…"></div>
                                <div class="md:col-span-3"><label class="a-label">Video overlay caption</label><input class="a-input" x-model="c.caption" placeholder="Strategy, design and engineering — under one roof."></div>
                                <div class="md:col-span-3">
                                    <p class="a-label">Stat chips</p>
                                    <template x-for="(row, i) in (c.stats ||= [])" :key="i">
                                        <div class="mb-2 grid grid-cols-[1fr_80px_2fr_auto] gap-2"><input class="a-input" x-model="row.value" placeholder="3.2"><input class="a-input" x-model="row.suffix" placeholder="x"><input class="a-input" x-model="row.label" placeholder="Average lead growth"><button type="button" class="text-red-500" @click="c.stats.splice(i, 1)">✕</button></div>
                                    </template>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="(c.stats ||= []).push({})">+ Add stat</button>
                                </div>
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
                                <div class="flex items-end gap-2">
                                    <div class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-lg border border-line bg-mist">
                                        <img x-show="c.image_id && thumbs[c.image_id]" :src="thumbs[c.image_id]" class="size-full object-cover" alt="">
                                        <x-icon x-show="!(c.image_id && thumbs[c.image_id])" name="image" class="size-4 text-slate-300" />
                                    </div>
                                    <div class="flex-1"><label class="a-label">Image media ID</label><input type="number" class="a-input" x-model.number="c.image_id"></div>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="openSectionPicker('image', (m) => { c.image_id = m.id; })">Choose</button>
                                </div>
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
                                <div><label class="a-label">Bottom CTA text</label><input class="a-input" x-model="c.cta_text" placeholder="Start with a free consultation"></div>
                                <div><label class="a-label">Bottom CTA URL (empty = popup)</label><input class="a-input" x-model="c.cta_url" placeholder="#enquiry-popup or /contact/"></div>
                                <div><label class="a-label">Bottom note</label><input class="a-input" x-model="c.note" placeholder="No obligation · Reply within one business day"></div>
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
                                <div class="md:col-span-3">
                                    <p class="a-label mb-2">Client logos & website links</p>
                                    <template x-for="(row, i) in (c.items ||= [])" :key="i">
                                        <div class="mb-2.5 grid gap-2 rounded-xl border border-line bg-mist/40 p-2.5 md:grid-cols-[auto_1.2fr_1.8fr_110px_auto] md:items-center">
                                            <div class="flex items-center gap-2">
                                                <div class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-lg border border-line bg-white">
                                                    <img x-show="row.logo_id && thumbs[row.logo_id]" :src="thumbs[row.logo_id]" :alt="row.name || 'Client logo'" class="size-full object-contain p-1">
                                                    <x-icon x-show="!(row.logo_id && thumbs[row.logo_id])" name="image" class="size-4 text-slate-300" />
                                                </div>
                                                <button type="button" class="a-btn a-btn-light a-btn-sm shrink-0" @click="openSectionPicker('image', (m) => { row.logo_id = m.id; })">Choose icon</button>
                                            </div>
                                            <input class="a-input" x-model="row.name" placeholder="Client name (e.g. AyurGrid)">
                                            <input class="a-input" x-model="row.url" placeholder="Website link (https://example.com)">
                                            <input type="number" class="a-input" x-model.number="row.logo_id" placeholder="Media ID">
                                            <button type="button" class="px-2 text-red-500 hover:text-red-700" @click="c.items.splice(i, 1)" title="Remove client">✕</button>
                                        </div>
                                    </template>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="(c.items ||= []).push({ name: '', url: '', logo_id: null })">+ Add client</button>
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
                            @case('industries')
                            @case('portfolio')
                            @case('testimonials')
                            @case('blogs')
                                <div><label class="a-label">Max items to display</label><input type="number" min="1" max="24" class="a-input" x-model.number="c.limit" placeholder="Default"></div>
                                <div><label class="a-label">Button text</label><input class="a-input" x-model="c.cta_text" placeholder="Default label"></div>
                                <div><label class="a-label">Button URL</label><input class="a-input" x-model="c.cta_url" placeholder="Default URL or #enquiry-popup"></div>
                                <p class="text-xs text-slate-500 md:col-span-3">Items in this section come from their own modules (Industries, Testimonials, Portfolio, Case Studies, Blogs).</p>
                                @break
                            @case('cta')
                                <div><label class="a-label">Button text</label><input class="a-input" x-model="c.button_text" placeholder="Get my free consultation"></div>
                                <div><label class="a-label">Button URL (empty = opens popup)</label><input class="a-input" x-model="c.button_url" placeholder="#enquiry-popup or /contact/"></div>
                                <div class="flex items-end gap-2">
                                    <div class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-lg border border-line bg-mist">
                                        <img x-show="c.image_id && thumbs[c.image_id]" :src="thumbs[c.image_id]" class="size-full object-cover" alt="">
                                        <x-icon x-show="!(c.image_id && thumbs[c.image_id])" name="image" class="size-4 text-slate-300" />
                                    </div>
                                    <div class="flex-1"><label class="a-label">Background image ID</label><input type="number" class="a-input" x-model.number="c.image_id" placeholder="Promo image fallback"></div>
                                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="openSectionPicker('image', (m) => { c.image_id = m.id; })">Choose</button>
                                </div>
                                <div class="md:col-span-3"><label class="a-label">Note under buttons</label><input class="a-input" x-model="c.note" placeholder="Free consultation · Reply within one business day"></div>
                                @break
                            @default
                                <p class="text-xs text-slate-500 md:col-span-3">This section pulls live entries from its dedicated Admin module.</p>
                        @endswitch
                    </div>
                </li>
            @endforeach
        </ul>

        {{-- Shared Media Picker Modal for Homepage Sections --}}
        <div x-show="pickerOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-4" @keydown.escape.window="pickerOpen = false">
            <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" @click.outside="pickerOpen = false">
                <div class="flex flex-wrap items-center gap-2 border-b border-line p-4">
                    <p class="flex-1 font-semibold text-ink" x-text="pickerType === 'video' ? 'Select MP4 video from Media Library' : 'Select image / icon from Media Library'"></p>
                    <input type="search" x-model="q" @input.debounce.350ms="loadLibrary(true)" placeholder="Search…" class="a-input w-48">
                    <label class="a-btn a-btn-primary cursor-pointer">
                        <x-icon name="plus" class="size-4" /> <span x-text="uploading ? 'Uploading…' : 'Upload'"></span>
                        <input type="file" class="hidden" :accept="pickerType === 'video' ? 'video/mp4' : 'image/*'" @change="uploadSectionMedia($event)">
                    </label>
                    <button type="button" class="a-btn a-btn-light" @click="pickerOpen = false">Close</button>
                </div>
                <div class="grid flex-1 grid-cols-3 gap-3 overflow-y-auto p-4 sm:grid-cols-5 lg:grid-cols-6">
                    <template x-for="m in library" :key="m.id">
                        <button type="button" @click="selectSectionMedia(m)" class="group flex flex-col overflow-hidden rounded-lg border border-line p-1.5 text-left transition hover:border-brand">
                            <img x-show="m.is_image" :src="m.thumb" :alt="m.alt" loading="lazy" class="aspect-video w-full object-contain bg-mist/40">
                            <span x-show="!m.is_image" class="grid aspect-video w-full place-items-center bg-ink p-2 text-center text-[0.65rem] text-white" x-text="m.filename"></span>
                            <span class="mt-1 truncate text-[0.68rem] text-slate-600" x-text="'#' + m.id + ' ' + m.filename"></span>
                        </button>
                    </template>
                    <p x-show="!loading && !library.length" class="col-span-full py-10 text-center text-sm text-slate-500">No matching files — upload one above.</p>
                </div>
                <div class="border-t border-line p-3 text-center" x-show="nextUrl"><button type="button" class="a-btn a-btn-light a-btn-sm" @click="loadLibrary(false)">Load more</button></div>
            </div>
        </div>
    </form>
</div>
@endsection
