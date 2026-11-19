import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Sortable from 'sortablejs';
import { Jodit } from 'jodit/es2021/jodit.min.js';
import 'jodit/es2021/jodit.min.css';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
const slugify = (s) => (s || '').toString().normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase()
    .replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').replace(/-{2,}/g, '-');
window.slugify = slugify;

/* ---------- Rich text editor (Jodit) on any <textarea data-editor> ---------- */
function initEditors(root = document) {
    root.querySelectorAll('textarea[data-editor]:not([data-editor-ready])').forEach((el) => {
        el.dataset.editorReady = '1';
        Jodit.make(el, {
            height: Number(el.dataset.height || 380),
            toolbarAdaptive: false,
            showCharsCounter: false,
            showXPathInStatusbar: false,
            askBeforePasteHTML: false,
            askBeforePasteFromWord: false,
            defaultActionOnPaste: 'insert_clear_html',
            buttons: ['paragraph', 'bold', 'italic', 'underline', '|', 'ul', 'ol', '|', 'link', 'image', 'table', '|', 'align', 'hr', 'eraser', '|', 'undo', 'redo', 'source', 'fullsize'],
            controls: { paragraph: { list: { p: 'Paragraph', h2: 'Heading 2', h3: 'Heading 3', h4: 'Heading 4', blockquote: 'Quote' } } },
            uploader: { insertImageAsBase64URI: false, url: el.dataset.uploadUrl || '', format: 'json', headers: { 'X-CSRF-TOKEN': csrf() } },
            cleanHTML: { denyTags: 'script,iframe,object,embed,style' },
        });
    });
}
window.initEditors = initEditors;

/* ---------- Media picker: <x-admin.media-picker name="featured_image_id" :media="$m" /> ---------- */
Alpine.data('mediaPicker', (cfg = {}) => ({
    open: false,
    multiple: !!cfg.multiple,
    value: cfg.value ?? (cfg.multiple ? [] : null),
    selected: cfg.selected ?? (cfg.multiple ? [] : null),
    items: [], next: null, q: '', loading: false, uploading: false,
    async load(reset = true) {
        this.loading = true;
        const url = reset ? `${cfg.indexUrl}?type=${cfg.type || "image"}&q=${encodeURIComponent(this.q)}` : this.next;
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const json = await res.json();
        this.items = reset ? json.data : [...this.items, ...json.data];
        this.next = json.next;
        this.loading = false;
    },
    show() { this.open = true; if (!this.items.length) this.load(); },
    pick(m) {
        if (this.multiple) {
            if (!this.value.includes(m.id)) { this.value.push(m.id); this.selected.push(m); }
        } else { this.value = m.id; this.selected = m; this.open = false; }
    },
    remove(id) {
        if (this.multiple) { this.value = this.value.filter((v) => v !== id); this.selected = this.selected.filter((s) => s.id !== id); }
        else { this.value = null; this.selected = null; }
    },
    async upload(e) {
        const files = [...e.target.files];
        if (!files.length) return;
        this.uploading = true;
        const fd = new FormData();
        files.forEach((f) => fd.append('files[]', f));
        const res = await fetch(cfg.uploadUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() }, body: fd });
        this.uploading = false;
        if (!res.ok) { alert('Upload failed — check file type and size.'); return; }
        const json = await res.json();
        this.items = [...json.data, ...this.items];
        if (json.data.length === 1) this.pick(json.data[0]);
        e.target.value = '';
    },
    async saveAlt(m) {
        await fetch(`${cfg.indexUrl}/${m.id}`, {
            method: 'PUT', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ alt: m.alt, title: m.title, caption: m.caption }),
        });
    },
}));

/* ---------- Repeater rows (benefits, process steps, pricing…) ---------- */
Alpine.data('repeater', (rows = [], blank = {}) => ({
    rows: (rows || []).map((r) => (typeof r === 'object' && r !== null ? r : { title: r })),
    add() { this.rows.push({ ...blank }); },
    remove(i) { this.rows.splice(i, 1); },
    move(i, d) { const j = i + d; if (j < 0 || j >= this.rows.length) return; [this.rows[i], this.rows[j]] = [this.rows[j], this.rows[i]]; },
}));

/* ---------- Page builder: blocks serialised to a hidden JSON input ---------- */
Alpine.data('blockBuilder', (initial = [], types = {}) => ({
    blocks: initial.map((b) => ({ uid: crypto.randomUUID(), type: b.type, is_visible: b.is_visible ?? true, open: false, data: b.data || {} })),
    types,
    adding: false,
    get json() { return JSON.stringify(this.blocks.map(({ type, is_visible, data }) => ({ type, is_visible, data }))); },
    add(type) {
        this.blocks.push({ uid: crypto.randomUUID(), type, is_visible: true, open: true, data: { background: 'white', align: 'left', items: [] } });
        this.adding = false;
        this.$nextTick(() => initEditors(this.$el));
    },
    remove(i) { if (confirm('Remove this block?')) this.blocks.splice(i, 1); },
    duplicate(i) { const c = JSON.parse(JSON.stringify(this.blocks[i])); c.uid = crypto.randomUUID(); this.blocks.splice(i + 1, 0, c); },
    move(i, d) { const j = i + d; if (j < 0 || j >= this.blocks.length) return; [this.blocks[i], this.blocks[j]] = [this.blocks[j], this.blocks[i]]; },
    addItem(b) { (b.data.items ||= []).push({}); },
    removeItem(b, k) { b.data.items.splice(k, 1); },
    init() {
        Sortable.create(this.$refs.list, {
            handle: '[data-handle]', animation: 150,
            onEnd: (e) => { const m = this.blocks.splice(e.oldIndex, 1)[0]; this.blocks.splice(e.newIndex, 0, m); },
        });
    },
}));

/* ---------- Character counter with recommended ranges ---------- */
Alpine.data('counter', (min, max, initial = '') => ({
    text: initial || '',
    get len() { return this.text.length; },
    get tone() { return this.len === 0 ? 'text-slate-400' : (this.len < min || this.len > max ? 'text-amber-600' : 'text-emerald-600'); },
}));

/* ---------- Generic sortable list → posts order to a URL ---------- */
Alpine.data('sortableList', (url) => ({
    init() {
        Sortable.create(this.$el, {
            handle: '[data-handle]', animation: 150,
            onEnd: async () => {
                const ids = [...this.$el.querySelectorAll('[data-id]')].map((el) => Number(el.dataset.id));
                if (url) await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body: JSON.stringify({ ids }) });
            },
        });
    },
}));

/* ---------- Drag-to-reorder list that writes positions into [data-order] hidden inputs ---------- */
Alpine.data('orderInputs', () => ({
    init() {
        const sync = () => [...this.$el.children].forEach((li, i) => { const inp = li.querySelector('[data-order]'); if (inp) inp.value = i; });
        Sortable.create(this.$el, { handle: '[data-handle]', animation: 150, onEnd: sync });
        sync();
    },
}));

/* ---------- Menu builder (nested, drag between levels) ---------- */
Alpine.data('menuBuilder', (tree = [], linkables = {}) => ({
    tree,
    linkables,
    edit: null,
    get json() { return JSON.stringify(this.tree); },
    blank() { return { label: 'New item', type: 'custom', url: '/', linkable_id: null, icon: '', description: '', is_mega: false, open_new_tab: false, is_active: true, children: [] }; },
    add(list) { list.push(this.blank()); this.edit = list[list.length - 1]; },
    remove(list, i) { if (confirm('Delete this item and its children?')) list.splice(i, 1); },
    move(list, i, d) { const j = i + d; if (j < 0 || j >= list.length) return; [list[i], list[j]] = [list[j], list[i]]; },
    indent(list, i) { if (i === 0) return; const [item] = list.splice(i, 1); (list[i - 1].children ||= []).push(item); },
    outdent(parentList, parentIndex, list, i) { if (!parentList) return; const [item] = list.splice(i, 1); parentList.splice(parentIndex + 1, 0, item); },
}));

/* ---------- Slug field that follows the title until edited manually ---------- */
Alpine.data('slugField', (initial = '', sourceName = 'title', locked = false) => ({
    slug: initial,
    touched: locked || !!initial,
    init() {
        const src = this.$root.closest('form')?.querySelector(`[name="${sourceName}"]`);
        src?.addEventListener('input', () => { if (!this.touched) this.slug = slugify(src.value); });
    },
    normalise() { this.slug = slugify(this.slug); this.touched = true; },
}));

/* ---------- Bulk selection on index tables ---------- */
Alpine.data('bulkSelect', () => ({
    ids: [],
    all: false,
    toggleAll(e) { this.ids = e.target.checked ? [...document.querySelectorAll('[data-row-id]')].map((c) => c.value) : []; },
}));

/* ---------- AI settings: per-provider connection test + model list (keys typed but not yet saved are sent once, over POST) ---------- */
Alpine.data('aiProvider', (cfg = {}) => ({
    model: cfg.model || '',
    key: '',
    busy: '',
    result: null,
    models: cfg.models || {},
    async post(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ provider: cfg.provider, ...body }),
        });
        if (!res.ok) return { ok: false, message: res.status === 429 ? 'Too many attempts. Wait a minute.' : `Request failed (${res.status}).` };
        return res.json();
    },
    async test() {
        this.busy = 'test';
        this.result = null;
        try { this.result = await this.post(cfg.testUrl, { model: this.model, key: this.key || null }); }
        catch { this.result = { ok: false, message: 'Network error.' }; }
        this.busy = '';
    },
    async fetchModels() {
        this.busy = 'models';
        try {
            const r = await this.post(cfg.modelsUrl, { key: this.key || null });
            if (r.ok) { this.models = r.models; this.result = { ok: true, message: `${Object.keys(r.models).length} models available. Pick one from the model field.` }; }
            else this.result = r;
        } catch { this.result = { ok: false, message: 'Network error.' }; }
        this.busy = '';
    },
}));

/* ---------- AI writing status on a page: polls until the job finishes, then reloads to show the new draft ---------- */
Alpine.data('aiStatus', (cfg = {}) => ({
    s: cfg.initial || {},
    timer: null,
    init() { if (this.s.pending) this.poll(); },
    poll() {
        this.timer = setTimeout(async () => {
            try {
                const res = await fetch(cfg.url, { headers: { Accept: 'application/json' } });
                if (res.ok) this.s = await res.json();
            } catch {}
            if (this.s.pending) return this.poll();
            if (this.s.status === 'succeeded') window.location.reload();
        }, 3000);
    },
    destroy() { clearTimeout(this.timer); },
}));

/* ---------- Unsaved changes guard ---------- */
document.addEventListener('DOMContentLoaded', () => {
    initEditors();
    document.querySelectorAll('form[data-guard]').forEach((form) => {
        let dirty = false;
        form.addEventListener('input', () => { dirty = true; });
        form.addEventListener('submit', () => { dirty = false; });
        window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    });
});

/* ---------- App shell: sidebar rail + collapsible groups (remembered per browser) and the Ctrl/⌘+K command menu ---------- */
const store = {
    get(k, d) { try { return JSON.parse(localStorage.getItem(k)) ?? d; } catch { return d; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch {} },
};

Alpine.data('adminShell', (cfg = {}) => ({
    nav: false,
    rail: store.get('admin.rail', false),
    closed: store.get('admin.navClosed', {}),
    palette: false,
    q: '',
    idx: 0,
    items: cfg.palette || [],

    init() {
        // The section holding the current page is always expanded.
        if (cfg.activeGroup && this.closed[cfg.activeGroup]) { delete this.closed[cfg.activeGroup]; store.set('admin.navClosed', this.closed); }
    },
    toggleRail() { this.rail = !this.rail; store.set('admin.rail', this.rail); },
    isOpen(group) { return !this.closed[group]; },
    toggleGroup(group) {
        this.closed = { ...this.closed, [group]: !this.closed[group] };
        store.set('admin.navClosed', this.closed);
    },

    get results() {
        const q = this.q.trim().toLowerCase();
        if (!q) return this.items.slice(0, 40);
        // Simple fuzzy match: every typed character appears in order in "label group".
        const score = (text) => {
            const t = text.toLowerCase();
            if (t.includes(q)) return 100 - t.indexOf(q);
            let pos = 0;
            for (const ch of q) { pos = t.indexOf(ch, pos); if (pos === -1) return -1; pos++; }
            return 1;
        };
        return this.items
            .map((item) => ({ item, s: Math.max(score(item.label), score(`${item.label} ${item.group}`) - 5) }))
            .filter((r) => r.s > 0)
            .sort((a, b) => b.s - a.s)
            .map((r) => r.item)
            .slice(0, 40);
    },
    openPalette() {
        this.palette = true;
        this.q = '';
        this.idx = 0;
        this.nav = false;
        this.$nextTick(() => this.$refs.paletteInput?.focus());
    },
    move(d) {
        const n = this.results.length;
        if (n) this.idx = (this.idx + d + n) % n;
    },
    go() {
        const item = this.results[this.idx];
        if (item) window.location.href = item.href;
    },
    onKey(e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); this.palette ? (this.palette = false) : this.openPalette(); return; }
        if (e.key === 'Escape' && this.palette) { this.palette = false; return; }
        // "/" opens the command menu unless the user is typing somewhere.
        const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName) || e.target.isContentEditable;
        if (e.key === '/' && !typing && !this.palette) { e.preventDefault(); this.openPalette(); }
    },
}));

window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.start();
