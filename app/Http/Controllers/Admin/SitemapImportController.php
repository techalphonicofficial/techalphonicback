<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Redirect;
use App\Models\SitemapImport;
use App\Models\UrlSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Legacy sitemap migration: import the old XML sitemap, see which URLs already exist in the new site,
 * map the rest to new URLs, then create 301s in one click so no ranking URL is lost.
 */
class SitemapImportController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);
        $items = SitemapImport::query()
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('q'), fn ($q, $s) => $q->where('old_url', 'like', '%'.addcslashes($s, '%_\\').'%'))
            ->orderBy('old_url')->paginate(50)->withQueryString();

        return view('admin.sitemap-import.index', [
            'items' => $items,
            'counts' => SitemapImport::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function import(Request $request)
    {
        $this->guard($request);
        $request->validate([
            'xml' => ['nullable', 'required_without:file', 'string', 'max:5000000'],
            'file' => ['nullable', 'required_without:xml', 'file', 'mimes:xml,txt', 'max:10240'],
        ]);
        $xml = $request->hasFile('file') ? file_get_contents($request->file('file')->getRealPath()) : $request->input('xml');

        // Disable external entities (XXE) — LIBXML_NONET blocks network access during parsing.
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string((string) $xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($prev);
        if (! $doc) {
            return back()->with('error', 'That does not look like a valid XML sitemap.');
        }

        $urls = [];
        foreach ($doc->children() as $node) {
            if (isset($node->loc)) {
                $urls[] = ['loc' => trim((string) $node->loc), 'lastmod' => isset($node->lastmod) ? (string) $node->lastmod : null];
            }
        }

        $created = 0;
        DB::transaction(function () use ($urls, &$created) {
            foreach ($urls as $u) {
                $path = Redirect::normalizePath($u['loc']);
                $slug = trim($path, '/');
                $exists = $path === '/' || UrlSlug::where('slug', $slug)->exists() || $this->matchesFixedRoute($path);

                $row = SitemapImport::firstOrNew(['old_url' => $path]);
                $row->fill([
                    'lastmod' => $u['lastmod'] ? rescue(fn () => Carbon::parse($u['lastmod']), null, false) : null,
                    'page_type' => $row->page_type ?: $this->guessType($path),
                ]);
                if (! $row->exists || $row->status === 'unmapped') {
                    $row->status = $exists ? 'mapped' : 'unmapped';
                    $row->new_url = $exists ? $path : $row->new_url;
                }
                $row->save();
                $created += $row->wasRecentlyCreated ? 1 : 0;
            }
        });

        ActivityLog::record('sitemap_imported', null, 'Imported '.count($urls).' legacy URLs');

        return back()->with('success', count($urls).' URLs processed ('.$created.' new). Map the "unmapped" ones, then create redirects.');
    }

    public function update(Request $request, int $id)
    {
        $this->guard($request);
        $row = SitemapImport::findOrFail($id);
        $data = $request->validate([
            'new_url' => ['nullable', 'string', 'max:500', 'regex:#^(/|https?://)#'],
            'status' => ['required', 'in:mapped,unmapped,ignored,redirected'],
            'page_type' => ['nullable', 'string', 'max:40'],
        ]);
        $row->update($data);

        return back()->with('success', 'Mapping saved.');
    }

    /** Create 301s for every mapped URL whose new path differs from the old one. */
    public function createRedirects(Request $request)
    {
        $this->guard($request);
        $count = 0;
        SitemapImport::where('status', 'mapped')->whereNotNull('new_url')->chunkById(500, function ($rows) use (&$count) {
            foreach ($rows as $row) {
                if (Redirect::normalizePath($row->new_url) !== $row->old_url) {
                    Redirect::updateOrCreate(['old_url' => $row->old_url], ['new_url' => $row->new_url, 'status_code' => 301, 'is_active' => true, 'source' => 'import']);
                    $row->update(['status' => 'redirected']);
                    $count++;
                }
            }
        });
        ActivityLog::record('sitemap_redirects_created', null, "$count redirects created from sitemap import");

        return back()->with('success', "$count redirects created.");
    }

    private function matchesFixedRoute(string $path): bool
    {
        return (bool) preg_match('#^/(blog|portfolio|case-studies|industries)(/|$)#', $path);
    }

    private function guessType(string $path): string
    {
        return match (true) {
            str_starts_with($path, '/blog/') => 'blog',
            str_starts_with($path, '/portfolio/') => 'portfolio',
            (bool) preg_match('#-(company|agency|services?)-in-#', $path) => 'service_location',
            (bool) preg_match('#^/(seo|ppc|smo)-for-|-seo-services/$#', $path) => 'service_industry',
            (bool) preg_match('#development|seo|marketing|design|ui-ux|branding|ppc|pay-per-click|social-media#', $path) => 'service',
            default => 'page',
        };
    }

    private function guard(Request $request): void
    {
        abort_unless($request->user()->can('redirects.manage'), 403);
    }
}
