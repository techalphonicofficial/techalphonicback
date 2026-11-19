<?php

namespace App\Console\Commands;

use App\Support\ContentCache;
use App\Support\LegacyHtml;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Content edited in the admin can contain absolute links to the site itself (pasted from a browser, or written
 * while the site ran on another host). Those break on staging/other domains and cost a redirect hop, so this
 * rewrites href="https://<own host>/x" as href="/x/" in every text column of the content tables.
 * Own hosts: APP_URL, FRONTEND_URL and any --host given. Safe to re-run; run it after moving domains.
 */
class RelativeContentLinks extends Command
{
    protected $signature = 'content:relative-links
        {--host=* : Extra host the site has lived on (e.g. the old domain), www. is matched automatically}
        {--dry-run : Only report what would change}';

    protected $description = 'Rewrite absolute links to the site itself as root-relative paths in stored content';

    /** Framework, log and lead tables: never content, and their URLs are records of what happened. */
    private const SKIP_TABLES = [
        'activity_logs', 'ai_generations', 'cache', 'cache_locks', 'failed_jobs', 'jobs', 'job_batches', 'leads',
        'lead_notes', 'migrations', 'password_reset_tokens', 'personal_access_tokens', 'redirects', 'sessions',
        'sitemap_imports', 'users',
    ];

    public function handle(): int
    {
        $hosts = collect([config('app.url'), config('services.frontend.url'), ...$this->option('host')])
            ->filter()
            ->map(fn ($h) => preg_replace('#^(https?://)?(www\.)?#i', '', rtrim((string) $h, '/')))
            ->filter()->unique()->values();

        if ($hosts->isEmpty()) {
            $this->error('No hosts: set APP_URL / FRONTEND_URL or pass --host.');

            return self::FAILURE;
        }
        $this->line('Own hosts: '.$hosts->implode(', '));

        // Current database only (the MySQL user may see other schemas), text-like columns only.
        $columns = collect(Schema::getTableListing(Schema::getCurrentSchemaName(), schemaQualified: false))
            ->reject(fn ($t) => in_array($t, self::SKIP_TABLES, true))
            ->flatMap(fn ($t) => collect(Schema::getColumns($t))
                ->filter(fn ($c) => in_array(strtolower($c['type_name']), ['varchar', 'text', 'mediumtext', 'longtext'], true))
                ->map(fn ($c) => (object) ['t' => $t, 'c' => $c['name']]));

        $changed = 0;
        foreach ($columns as $col) {
            $query = DB::table($col->t)->where(function ($q) use ($col, $hosts) {
                foreach ($hosts as $host) {
                    $q->orWhere($col->c, 'like', '%href="http%'.$host.'%');
                }
            });
            $key = isset((clone $query)->first()->id) ? 'id' : null;

            foreach ($query->get() as $row) {
                $html = $row->{$col->c};
                $new = $hosts->reduce(fn ($h, $host) => (new LegacyHtml($host))->relativeLinks($h), $html);
                if ($new === $html) {
                    continue;
                }
                $changed++;
                $this->line(sprintf('%s.%s #%s', $col->t, $col->c, $key ? $row->id : '?'));
                if (! $this->option('dry-run') && $key) {
                    DB::table($col->t)->where('id', $row->id)->update([$col->c => $new]);
                }
            }
        }

        if ($changed && ! $this->option('dry-run')) {
            ContentCache::flush();
        }
        $this->info(($this->option('dry-run') ? 'Would update ' : 'Updated ').$changed.' value(s).');

        return self::SUCCESS;
    }
}
