@extends('admin.layouts.app')

@section('title', 'Menu: '.$menu->name)

@section('content')
<a href="{{ route('admin.menus.index') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-ink">← All menus</a>
<form method="POST" action="{{ route('admin.menus.update', $menu->location) }}" x-data="menuBuilder(@js($tree), @js($linkables))" class="grid gap-6 xl:grid-cols-[1fr_380px]" data-guard>
    @csrf @method('PUT')
    <input type="hidden" name="tree" :value="json">

    <div class="a-card">
        <div class="a-card-h">
            <p class="a-card-t">Items</p>
            <button type="button" class="a-btn a-btn-light a-btn-sm" @click="add(tree)">+ Add top-level item</button>
        </div>
        <div class="a-card-b">
            <p class="mb-3 text-xs text-slate-500">For a mega menu: add a top-level item (e.g. “Services”), tick <em>Mega menu</em>, add <em>Heading</em> children (“Digital Marketing”, “Development”), and put links under each heading. Use ▲▼ to reorder, → / ← to nest.</p>
            <template x-if="! tree.length"><p class="rounded-xl border border-dashed border-line p-8 text-center text-sm text-slate-500">Empty menu.</p></template>
            <ul class="grid gap-1.5">
                <template x-for="(a, i) in tree" :key="'a' + i">
                    <li>
                        <div class="flex items-center gap-2 rounded-lg border bg-white px-3 py-2" :class="edit === a ? 'border-brand ring-2 ring-brand/20' : 'border-line'">
                            <button type="button" class="flex-1 text-left text-sm font-semibold text-ink" @click="edit = a"><span x-text="a.label"></span> <span class="ml-1 text-xs font-normal text-slate-400" x-text="a.type + (a.is_mega ? ' · mega' : '') + (a.is_active ? '' : ' · hidden')"></span></button>
                            <button type="button" class="text-slate-400 hover:text-ink" @click="move(tree, i, -1)">▲</button>
                            <button type="button" class="text-slate-400 hover:text-ink" @click="move(tree, i, 1)">▼</button>
                            <button type="button" class="text-slate-400 hover:text-ink" title="Nest under previous" @click="indent(tree, i)">→</button>
                            <button type="button" class="text-xs text-brand" @click="add(a.children ||= [])">+ child</button>
                            <button type="button" class="text-red-500" @click="remove(tree, i)">✕</button>
                        </div>
                        <ul class="ml-6 mt-1.5 grid gap-1.5 border-l-2 border-line pl-3" x-show="a.children && a.children.length">
                            <template x-for="(b, j) in a.children" :key="'b' + i + '-' + j">
                                <li>
                                    <div class="flex items-center gap-2 rounded-lg border bg-white px-3 py-1.5" :class="edit === b ? 'border-brand ring-2 ring-brand/20' : 'border-line'">
                                        <button type="button" class="flex-1 text-left text-sm text-ink" @click="edit = b"><span x-text="b.label"></span> <span class="ml-1 text-xs text-slate-400" x-text="b.type"></span></button>
                                        <button type="button" class="text-slate-400" @click="move(a.children, j, -1)">▲</button>
                                        <button type="button" class="text-slate-400" @click="move(a.children, j, 1)">▼</button>
                                        <button type="button" class="text-slate-400" title="Un-nest" @click="outdent(tree, i, a.children, j)">←</button>
                                        <button type="button" class="text-slate-400" title="Nest" @click="indent(a.children, j)">→</button>
                                        <button type="button" class="text-xs text-brand" @click="add(b.children ||= [])">+ child</button>
                                        <button type="button" class="text-red-500" @click="remove(a.children, j)">✕</button>
                                    </div>
                                    <ul class="ml-6 mt-1 grid gap-1 border-l-2 border-line pl-3" x-show="b.children && b.children.length">
                                        <template x-for="(c, k) in b.children" :key="'c' + i + '-' + j + '-' + k">
                                            <li>
                                                <div class="flex items-center gap-2 rounded-lg border bg-white px-3 py-1.5" :class="edit === c ? 'border-brand ring-2 ring-brand/20' : 'border-line'">
                                                    <button type="button" class="flex-1 text-left text-xs text-ink" @click="edit = c"><span x-text="c.label"></span> <span class="ml-1 text-[0.68rem] text-slate-400" x-text="c.type"></span></button>
                                                    <button type="button" class="text-slate-400" @click="move(b.children, k, -1)">▲</button>
                                                    <button type="button" class="text-slate-400" @click="move(b.children, k, 1)">▼</button>
                                                    <button type="button" class="text-slate-400" title="Un-nest" @click="outdent(a.children, j, b.children, k)">←</button>
                                                    <button type="button" class="text-slate-400" title="Nest" @click="indent(b.children, k)">→</button>
                                                    <button type="button" class="text-xs text-brand" @click="add(c.children ||= [])">+ child</button>
                                                    <button type="button" class="text-red-500" @click="remove(b.children, k)">✕</button>
                                                </div>
                                                <ul class="ml-6 mt-1 grid gap-1 border-l-2 border-line pl-3" x-show="c.children && c.children.length">
                                                    <template x-for="(d, l) in c.children" :key="'d' + i + '-' + j + '-' + k + '-' + l">
                                                        <li class="flex items-center gap-2 rounded-lg border bg-white px-3 py-1.5" :class="edit === d ? 'border-brand ring-2 ring-brand/20' : 'border-line'">
                                                            <button type="button" class="flex-1 text-left text-xs text-ink" @click="edit = d"><span x-text="d.label"></span> <span class="ml-1 text-[0.68rem] text-slate-400" x-text="d.type"></span></button>
                                                            <button type="button" class="text-slate-400" @click="move(c.children, l, -1)">▲</button>
                                                            <button type="button" class="text-slate-400" @click="move(c.children, l, 1)">▼</button>
                                                            <button type="button" class="text-slate-400" title="Un-nest" @click="outdent(b.children, k, c.children, l)">←</button>
                                                            <button type="button" class="text-red-500" @click="remove(c.children, l)">✕</button>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </li>
                                        </template>
                                    </ul>
                                </li>
                            </template>
                        </ul>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    <aside class="grid content-start gap-4">
        <div class="a-card">
            <div class="a-card-h"><p class="a-card-t">Menu settings</p></div>
            <div class="a-card-b grid gap-3">
                <x-admin.field name="name" label="Menu name" :value="$menu->name" required />
                <x-admin.field type="checkbox" name="is_active" label="Menu enabled" :value="$menu->is_active" />
                <button class="a-btn a-btn-primary">Save menu</button>
            </div>
        </div>

        <div class="a-card" x-show="edit" x-cloak>
            <div class="a-card-h"><p class="a-card-t">Edit item</p><button type="button" @click="edit = null"><x-icon name="x" class="size-4" /></button></div>
            <template x-if="edit">
                <div class="a-card-b grid gap-3">
                    <div><label class="a-label">Label</label><input class="a-input" x-model="edit.label"></div>
                    <div><label class="a-label">Type</label>
                        <select class="a-input" x-model="edit.type">
                            <option value="custom">Custom URL</option><option value="heading">Column heading (no link)</option>
                            <option value="page">Page</option><option value="service_category">Service category</option><option value="service">Service</option>
                            <option value="landing_page">Programmatic page</option><option value="blog">Blog post</option>
                        </select></div>
                    <div x-show="edit.type === 'custom'"><label class="a-label">URL</label><input class="a-input" x-model="edit.url" placeholder="/contact/ or https://"></div>
                    <template x-if="linkables[edit.type]">
                        <div><label class="a-label">Link to</label>
                            <select class="a-input" x-model.number="edit.linkable_id">
                                <option value="">— choose —</option>
                                <template x-for="o in linkables[edit.type]" :key="o.id"><option :value="o.id" x-text="o.label" :selected="o.id === edit.linkable_id"></option></template>
                            </select></div>
                    </template>
                    <div><label class="a-label">Icon (mega menu)</label>
                        <select class="a-input" x-model="edit.icon"><option value="">None</option>@foreach($icons as $ic)<option value="{{ $ic }}">{{ $ic }}</option>@endforeach</select></div>
                    <div><label class="a-label">Short description (mega menu)</label><input class="a-input" x-model="edit.description"></div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="a-check" x-model="edit.is_mega"> Mega menu (top level)</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="a-check" x-model="edit.open_new_tab"> Open in new tab</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="a-check" x-model="edit.is_active"> Visible</label>
                </div>
            </template>
        </div>
    </aside>
</form>
@endsection
