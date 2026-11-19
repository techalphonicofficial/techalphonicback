@props(['item'])
<input type="hidden" name="links_present" value="1">
<p class="mb-3 text-sm text-slate-600">Related services, locations, industries and blogs are linked automatically. Add hand-picked links here (shown first, max 20). Use relative URLs like <code>/laravel-development/</code>.</p>
<x-admin.repeater name="links" label="Manual internal links"
    :rows="$item->exists ? $item->internalLinks->map(fn ($l) => ['anchor' => $l->anchor, 'url' => $l->url])->all() : []"
    :fields="['anchor' => 'Anchor text', 'url' => 'URL']" add-label="Add link" />
