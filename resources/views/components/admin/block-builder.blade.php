@props(['item'])
@php
    $initial = old('blocks_json') ? json_decode(old('blocks_json'), true) : ($item->exists ? $item->blocks->map(fn ($b) => ['type' => $b->type, 'is_visible' => $b->is_visible, 'data' => $b->data ?? []])->all() : []);
    $types = \App\Models\PageBlock::TYPES;
    if (! auth()->user()->can('custom_html.use')) {
        unset($types['custom_html']);
    }
    // Which editor fields each block type shows.
    $schema = [
        'common' => ['eyebrow', 'heading', 'subheading', 'background', 'align', 'anchor'],
        'content' => ['hero', 'text', 'image_text', 'cta', 'contact_form'],
        'image' => ['hero', 'image_text', 'video'],
        'cta' => ['hero', 'text', 'image_text', 'cta'],
        'items' => ['two_column', 'three_column', 'feature_cards', 'benefits', 'statistics', 'process', 'timeline', 'technologies', 'pricing', 'logo_cloud', 'image_text'],
        'limit' => ['service_cards', 'related_blogs', 'related_locations', 'testimonials', 'case_studies', 'industries'],
    ];
@endphp
<div x-data="blockBuilder(@js($initial), @js($types))" class="grid gap-3">
    <input type="hidden" name="blocks_json" :value="json">
    <p class="text-sm text-slate-600">Build the page from reusable sections. Drag <x-icon name="menu" class="inline size-3.5" /> to reorder. Tip: wrap words in <code>[[double brackets]]</code> to highlight them in headings. Programmatic pages can use <code>@{{location_name}}</code>-style variables.</p>

    <div x-ref="list" class="grid gap-3">
        <template x-for="(b, i) in blocks" :key="b.uid">
            <div class="rounded-xl border border-line bg-white" :class="! b.is_visible && 'opacity-60'">
                <div class="flex items-center gap-2 px-3 py-2.5">
                    <span data-handle class="cursor-grab text-slate-400 hover:text-ink" title="Drag to reorder"><x-icon name="menu" class="size-4" /></span>
                    <button type="button" class="flex flex-1 items-center gap-2 text-left" @click="b.open = ! b.open">
                        <span class="a-badge bg-ink text-white" x-text="types[b.type] || b.type"></span>
                        <span class="truncate text-sm text-slate-600" x-text="b.data.heading || ''"></span>
                    </button>
                    <label class="flex items-center gap-1 text-xs text-slate-500"><input type="checkbox" class="a-check" x-model="b.is_visible"> Visible</label>
                    <button type="button" class="rounded p-1 text-slate-400 hover:text-ink" @click="move(i, -1)" title="Up">▲</button>
                    <button type="button" class="rounded p-1 text-slate-400 hover:text-ink" @click="move(i, 1)" title="Down">▼</button>
                    <button type="button" class="rounded p-1 text-slate-400 hover:text-ink" @click="duplicate(i)" title="Duplicate"><x-icon name="layers" class="size-4" /></button>
                    <button type="button" class="rounded p-1 text-slate-400 hover:text-red-600" @click="remove(i)" title="Delete"><x-icon name="x" class="size-4" /></button>
                </div>

                <div x-show="b.open" x-collapse class="grid gap-3 border-t border-line p-4 md:grid-cols-2">
                    <div><label class="a-label">Eyebrow</label><input class="a-input" x-model="b.data.eyebrow"></div>
                    <div><label class="a-label">Heading (H2)</label><input class="a-input" x-model="b.data.heading"></div>
                    <div class="md:col-span-2"><label class="a-label">Subheading</label><input class="a-input" x-model="b.data.subheading"></div>

                    <template x-if="@js($schema['content']).includes(b.type)">
                        <div class="md:col-span-2"><label class="a-label">Content (HTML allowed)</label><textarea rows="5" class="a-input font-mono text-xs" x-model="b.data.content"></textarea></div>
                    </template>

                    <template x-if="@js($schema['image']).includes(b.type)">
                        <div><label class="a-label">Image ID</label>
                            <input type="number" class="a-input" x-model.number="b.data.image_id" placeholder="Media ID">
                            <p class="a-help">Find the ID in <a href="{{ route('admin.media.index') }}" target="_blank" class="text-brand">Media Library ↗</a></p></div>
                    </template>
                    <template x-if="b.type === 'image_text'">
                        <div><label class="a-label">Image position</label><select class="a-input" x-model="b.data.image_position"><option value="left">Left</option><option value="right">Right</option></select></div>
                    </template>
                    <template x-if="b.type === 'video'">
                        <div class="md:col-span-2"><label class="a-label">Video URL (YouTube or .mp4)</label><input class="a-input" x-model="b.data.video_url"></div>
                    </template>
                    <template x-if="b.type === 'gallery'">
                        <div class="md:col-span-2"><label class="a-label">Gallery media IDs (comma separated)</label>
                            <input class="a-input" :value="(b.data.gallery_ids || []).join(', ')" @change="b.data.gallery_ids = $event.target.value.split(',').map(s => parseInt(s)).filter(Boolean)"></div>
                    </template>
                    <template x-if="b.type === 'comparison_table'">
                        <div class="md:col-span-2"><label class="a-label">Table (one row per line, cells separated by | — first row is the header)</label>
                            <textarea rows="6" class="a-input font-mono text-xs" x-model="b.data.table" placeholder="Feature | Us | Others&#10;Dedicated manager | Yes | No"></textarea></div>
                    </template>
                    <template x-if="b.type === 'custom_html'">
                        <div class="md:col-span-2"><label class="a-label">Custom HTML</label><textarea rows="8" class="a-input font-mono text-xs" x-model="b.data.html"></textarea></div>
                    </template>
                    <template x-if="b.type === 'service_cards'">
                        <div><label class="a-label">Service IDs (optional, comma separated)</label>
                            <input class="a-input" :value="(b.data.service_ids || []).join(', ')" @change="b.data.service_ids = $event.target.value.split(',').map(s => parseInt(s)).filter(Boolean)"></div>
                    </template>
                    <template x-if="@js($schema['limit']).includes(b.type)">
                        <div><label class="a-label">Max items</label><input type="number" min="1" max="24" class="a-input" x-model.number="b.data.limit"></div>
                    </template>
                    <template x-if="@js($schema['cta']).includes(b.type)">
                        <div class="grid grid-cols-2 gap-2 md:col-span-2">
                            <div><label class="a-label">Button text</label><input class="a-input" x-model="b.data.cta_text"></div>
                            <div><label class="a-label">Button URL</label><input class="a-input" x-model="b.data.cta_url" placeholder="/contact/"></div>
                        </div>
                    </template>

                    <template x-if="@js($schema['items']).includes(b.type)">
                        <div class="md:col-span-2">
                            <div class="mb-2 flex items-center justify-between"><p class="a-label !mb-0">Items</p><button type="button" class="a-btn a-btn-light a-btn-sm" @click="addItem(b)">+ Add item</button></div>
                            <div class="grid gap-2">
                                <template x-for="(it, k) in (b.data.items || [])" :key="k">
                                    <div class="grid gap-2 rounded-lg border border-line bg-mist/60 p-2 sm:grid-cols-[1fr_1fr_120px_auto]">
                                        <input class="a-input" x-model="it.title" placeholder="Title / label">
                                        <input class="a-input" x-model="it.text" placeholder="Text">
                                        <input class="a-input" x-model="it.icon" placeholder="icon / image id" list="icon-names">
                                        <button type="button" class="text-red-500" @click="removeItem(b, k)">✕</button>
                                        <template x-if="['statistics','pricing','timeline'].includes(b.type)">
                                            <div class="grid grid-cols-2 gap-2 sm:col-span-4">
                                                <input class="a-input" x-model="it.value" placeholder="Value (e.g. 250 or ₹9,999)">
                                                <input class="a-input" x-model="it.suffix" placeholder="Suffix (e.g. + or /month)">
                                            </div>
                                        </template>
                                        <template x-if="b.type === 'pricing'">
                                            <div class="grid gap-2 sm:col-span-4">
                                                <textarea class="a-input" rows="3" x-model="it.features" placeholder="Features (one per line)"></textarea>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <input class="a-input" x-model="it.cta_text" placeholder="Button text (default: Get Started)">
                                                    <input class="a-input" x-model="it.badge" placeholder="Badge (default: Most Popular)">
                                                </div>
                                                <label class="text-xs"><input type="checkbox" class="a-check" x-model="it.featured"> Highlight as most popular</label>
                                            </div>
                                        </template>
                                        <template x-if="['feature_cards','pricing','logo_cloud','technologies'].includes(b.type)">
                                            <div class="grid grid-cols-2 gap-2 sm:col-span-4">
                                                <input class="a-input" x-model="it.url" placeholder="Link URL (optional)">
                                                <input type="number" class="a-input" x-model.number="it.image_id" placeholder="Logo / image media ID">
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="grid grid-cols-3 gap-2 md:col-span-2">
                        <div><label class="a-label">Background</label><select class="a-input" x-model="b.data.background"><option value="white">White</option><option value="mist">Light grey</option><option value="dark">Dark</option><option value="brand">Brand orange</option></select></div>
                        <div><label class="a-label">Alignment</label><select class="a-input" x-model="b.data.align"><option value="left">Left</option><option value="center">Center</option></select></div>
                        <div><label class="a-label">Anchor ID</label><input class="a-input" x-model="b.data.anchor" placeholder="e.g. pricing"></div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <datalist id="icon-names">@foreach(\App\Support\Icons::names() as $ic)<option value="{{ $ic }}">@endforeach</datalist>

    <div class="relative">
        <button type="button" class="a-btn a-btn-dark w-full justify-center" @click="adding = ! adding"><x-icon name="plus" class="size-4" /> Add block</button>
        <div x-show="adding" x-cloak @click.outside="adding = false" class="absolute inset-x-0 z-10 mt-2 grid grid-cols-2 gap-1 rounded-xl border border-line bg-white p-2 shadow-xl sm:grid-cols-4">
            <template x-for="(label, type) in types" :key="type">
                <button type="button" class="rounded-lg px-3 py-2 text-left text-sm hover:bg-brand-50 hover:text-brand" @click="add(type)" x-text="label"></button>
            </template>
        </div>
    </div>
</div>
