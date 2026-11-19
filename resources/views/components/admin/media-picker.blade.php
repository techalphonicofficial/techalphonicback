@props(['name', 'label' => 'Image', 'media' => null, 'multiple' => false, 'help' => null, 'type' => 'image'])
@php
    $toArr = fn ($m) => $m ? ['id' => $m->id, 'thumb' => $m->webp_url ?: $m->url, 'filename' => $m->filename, 'alt' => $m->alt, 'is_image' => $m->isImage()] : null;
    $selected = $multiple ? collect($media)->map($toArr)->values()->all() : $toArr($media);
    $value = $multiple ? collect($media)->pluck('id')->all() : $media?->id;
@endphp
<div x-data="mediaPicker({ multiple: @js($multiple), value: @js($value), selected: @js($selected), indexUrl: @js(route('admin.media.index')), uploadUrl: @js(route('admin.media.store')), type: @js($type) })">
    <p class="a-label">{{ $label }}</p>

    @if($multiple)
        <template x-for="id in value" :key="id"><input type="hidden" name="{{ $name }}[]" :value="id"></template>
        <div class="flex flex-wrap gap-2">
            <template x-for="m in selected" :key="m.id">
                <div class="group relative size-20 overflow-hidden rounded-lg border border-line bg-mist">
                    <img :src="m.thumb" :alt="m.alt" class="size-full object-cover">
                    <button type="button" @click="remove(m.id)" class="absolute right-1 top-1 hidden rounded bg-white/90 p-0.5 text-red-600 group-hover:block" aria-label="Remove"><x-icon name="x" class="size-3.5" /></button>
                </div>
            </template>
            <button type="button" @click="show()" class="grid size-20 place-items-center rounded-lg border-2 border-dashed border-line text-slate-400 hover:border-brand hover:text-brand"><x-icon name="plus" class="size-5" /></button>
        </div>
    @else
        <input type="hidden" name="{{ $name }}" :value="value ?? ''">
        <div class="flex items-center gap-3">
            <div class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-lg border border-line bg-mist">
                <template x-if="selected && selected.is_image !== false"><img :src="selected.thumb" :alt="selected.alt" class="size-full object-cover"></template>
                <template x-if="selected && selected.is_image === false"><x-icon name="play" class="size-7 text-brand" /></template>
                <template x-if="! selected"><x-icon name="image" class="size-6 text-slate-300" /></template>
            </div>
            <div class="min-w-0">
                <p class="truncate text-xs text-slate-500" x-text="selected ? selected.filename : 'No image selected'"></p>
                <p class="truncate text-xs" x-show="selected" :class="selected && selected.alt ? 'text-emerald-600' : 'text-amber-600'" x-text="selected && selected.alt ? 'Alt: ' + selected.alt : 'Missing alt text'"></p>
                <div class="mt-1.5 flex gap-1.5">
                    <button type="button" class="a-btn a-btn-light a-btn-sm" @click="show()">Choose</button>
                    <button type="button" class="a-btn a-btn-light a-btn-sm text-red-600" x-show="selected" @click="remove()">Remove</button>
                </div>
            </div>
        </div>
    @endif
    @if($help)<p class="a-help">{{ $help }}</p>@endif
    @error($name)<p class="a-error">{{ $message }}</p>@enderror

    {{-- Modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-4" @keydown.escape.window="open = false">
        <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" @click.outside="open = false">
            <div class="flex flex-wrap items-center gap-2 border-b border-line p-4">
                <p class="flex-1 font-semibold text-ink">Media library</p>
                <input type="search" x-model="q" @input.debounce.350ms="load()" placeholder="Search…" class="a-input w-48">
                <label class="a-btn a-btn-primary cursor-pointer">
                    <x-icon name="plus" class="size-4" /> <span x-text="uploading ? 'Uploading…' : 'Upload'"></span>
                    <input type="file" class="hidden" multiple accept="{{ $type === 'video' ? 'video/mp4' : 'image/*' }}" @change="upload($event)">
                </label>
                <button type="button" class="a-btn a-btn-light" @click="open = false">Done</button>
            </div>
            <div class="grid flex-1 grid-cols-3 gap-3 overflow-y-auto p-4 sm:grid-cols-5 lg:grid-cols-6">
                <template x-for="m in items" :key="m.id">
                    <div class="group relative">
                        <button type="button" @click="pick(m)" class="block aspect-square w-full overflow-hidden rounded-lg border-2 transition"
                                :class="(multiple ? value.includes(m.id) : value === m.id) ? 'border-brand ring-2 ring-brand/30' : 'border-transparent hover:border-line'">
                            <img x-show="m.is_image" :src="m.thumb" :alt="m.alt" loading="lazy" class="size-full object-cover">
                            <span x-show="! m.is_image" class="grid size-full place-items-center bg-ink p-2 text-center text-[0.65rem] text-white" x-text="m.filename"></span>
                        </button>
                        <input type="text" x-model="m.alt" @change="saveAlt(m)" placeholder="Alt text…" class="mt-1 w-full rounded border border-line px-1.5 py-1 text-[0.7rem]" :class="! m.alt && 'border-amber-300 bg-amber-50'">
                    </div>
                </template>
                <p x-show="! loading && ! items.length" class="col-span-full py-10 text-center text-sm text-slate-500">No images yet — upload one.</p>
            </div>
            <div class="border-t border-line p-3 text-center" x-show="next"><button type="button" class="a-btn a-btn-light a-btn-sm" @click="load(false)">Load more</button></div>
        </div>
    </div>
</div>
