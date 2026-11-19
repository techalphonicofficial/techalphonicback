<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\FrontendRevalidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Admin → Maintenance: the artisan commands a deploy needs, for hosts without SSH, plus environment checks.
 * Only the commands listed in ACTIONS can run — nothing is taken from the request except the action key.
 */
class MaintenanceController extends Controller
{
    /** key => [label, help, artisan commands [name, args], refresh the Next.js cache afterwards, needs confirmation] */
    private const ACTIONS = [
        'refresh' => ['Clear & rebuild caches', 'php artisan optimize:clear && php artisan optimize — run after every deploy or .env change. Also refreshes the website.', [['optimize:clear', []], ['optimize', []]], true, false],
        'clear' => ['Clear all caches', 'php artisan optimize:clear — config, routes, views, events and the application cache. Leaves them uncached until rebuilt.', [['optimize:clear', []]], true, false],
        'cache' => ['Clear application cache', 'php artisan cache:clear — content, menus, settings and redirect caches.', [['cache:clear', []]], true, false],
        'views' => ['Clear compiled views', 'php artisan view:clear — after uploading changed admin templates.', [['view:clear', []]], false, false],
        'storage-link' => ['Create storage link', 'php artisan storage:link --force — makes uploaded media reachable under /storage. Run once per server, or if images return 403/404.', [['storage:link', ['--force' => true]]], false, false],
        'queue-restart' => ['Restart queue workers', 'php artisan queue:restart — workers pick up new code after their current job (emails are queued).', [['queue:restart', []]], false, false],
        'publish-scheduled' => ['Publish scheduled content', 'php artisan content:publish-scheduled — normally runs every minute via the scheduler.', [['content:publish-scheduled', []]], false, false],
        'relative-links' => ['Fix absolute links in content', 'php artisan content:relative-links — rewrites links to this site saved as full URLs into /relative/ paths.', [['content:relative-links', []]], true, false],
        'migrate' => ['Run database migrations', 'php artisan migrate --force — applies pending schema changes after a deploy. Back up the database first.', [['migrate', ['--force' => true]]], false, true],
    ];

    public function index()
    {
        $this->authorizeMaintenance();

        return view('admin.maintenance.index', [
            'actions' => collect(self::ACTIONS)->map(fn ($a, $key) => ['key' => $key, 'label' => $a[0], 'help' => $a[1], 'confirm' => $a[4]]),
            'checks' => $this->checks(),
            'info' => [
                'Environment' => app()->environment(),
                'APP_URL' => config('app.url'),
                'FRONTEND_URL' => config('services.frontend.url') ?: '—',
                'Laravel' => app()->version(),
                'PHP' => PHP_VERSION,
                'Cache store' => config('cache.default'),
                'Queue' => config('queue.default'),
            ],
        ]);
    }

    public function run(Request $request, string $action)
    {
        $this->authorizeMaintenance();
        abort_unless(isset(self::ACTIONS[$action]), 404);
        [$label, , $commands, $thenFrontend] = self::ACTIONS[$action];

        @set_time_limit(300);
        $output = [];
        $ok = true;
        foreach ($commands as [$command, $args]) {
            $buffer = new BufferedOutput;
            try {
                $code = Artisan::call($command, $args, $buffer);
            } catch (\Throwable $e) {
                $code = 1;
                $buffer->writeln($e->getMessage());
            }
            $output[] = '$ php artisan '.$command.collect($args)->map(fn ($v, $k) => $v === true ? " $k" : " $k=$v")->implode('');
            $output[] = trim(preg_replace('/\e\[[\d;]*m/', '', $buffer->fetch())) ?: '(no output)';
            if ($code !== 0) {
                $ok = false;
                break;
            }
        }

        if ($ok && $thenFrontend) {
            $output[] = '$ refresh website cache (Next.js)';
            $output[] = $this->frontendLine(FrontendRevalidator::revalidateNow());
        }

        ActivityLog::record('maintenance', null, $label.($ok ? '' : ' (failed)'), ['action' => $action]);

        return back()
            ->with($ok ? 'success' : 'error', $ok ? $label.' finished.' : $label.' failed — see the output below.')
            ->with('maintenance_output', implode("\n", $output));
    }

    public function frontend(string $action)
    {
        $this->authorizeMaintenance();
        abort_unless(in_array($action, ['revalidate', 'status'], true), 404);

        if ($action === 'revalidate') {
            $result = FrontendRevalidator::revalidateNow();
            ActivityLog::record('maintenance', null, 'Refresh website cache'.($result['ok'] ? '' : ' (failed)'));

            return back()
                ->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Website cache refreshed. Pages re-render on their next visit.' : $result['error'])
                ->with('maintenance_output', '$ refresh website cache (Next.js)'."\n".$this->frontendLine($result));
        }

        return back()->with('frontend_status', FrontendRevalidator::status());
    }

    private function frontendLine(array $result): string
    {
        return $result['ok'] ? 'OK — '.config('services.frontend.url').' revalidated.' : 'Failed — '.$result['error'];
    }

    /** @return array<int, array{label: string, ok: bool, detail: string}> */
    private function checks(): array
    {
        $prod = app()->isProduction();
        $isLocalUrl = fn (?string $url) => (bool) preg_match('#^https?://(localhost|127\.|0\.0\.0\.0|\[::1\])#i', (string) $url);
        $appUrl = (string) config('app.url');
        $frontend = (string) config('services.frontend.url');
        $storageTarget = realpath(storage_path('app/public'));
        $storageLink = realpath(public_path('storage'));
        $pending = $this->pendingMigrations();

        return [
            ['label' => 'Debug mode off', 'ok' => ! ($prod && config('app.debug')), 'detail' => config('app.debug') ? 'APP_DEBUG=true'.($prod ? ' — exposes errors and secrets to visitors' : ' (fine locally)') : 'APP_DEBUG=false'],
            ['label' => 'APP_URL is the public domain', 'ok' => ! ($prod && ($isLocalUrl($appUrl) || ! str_starts_with($appUrl, 'https://'))), 'detail' => $appUrl.' — canonicals, sitemaps, schema and media URLs use it'],
            ['label' => 'FRONTEND_URL set', 'ok' => $frontend !== '' && ! ($prod && $isLocalUrl($frontend)), 'detail' => ($frontend ?: 'not set').' — public URLs reaching Laravel are 301\'d here'],
            ['label' => 'Frontend keys set', 'ok' => config('services.frontend.key') && config('services.frontend.revalidate_secret'), 'detail' => 'FRONTEND_API_KEY and FRONTEND_REVALIDATE_SECRET (must match the frontend)'],
            ['label' => 'Storage link', 'ok' => $storageLink && $storageLink === $storageTarget, 'detail' => $storageLink === $storageTarget ? 'public/storage → storage/app/public' : 'missing or pointing elsewhere — run "Create storage link"'],
            ['label' => 'Storage writable', 'ok' => is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')), 'detail' => 'storage/ and bootstrap/cache/'],
            ['label' => 'Migrations up to date', 'ok' => $pending === 0, 'detail' => $pending === null ? 'could not be checked' : ($pending ? $pending.' pending — run "Run database migrations"' : 'none pending')],
            ['label' => 'Config & routes cached', 'ok' => ! $prod || (app()->configurationIsCached() && app()->routesAreCached()), 'detail' => (app()->configurationIsCached() ? 'config cached' : 'config not cached').', '.(app()->routesAreCached() ? 'routes cached' : 'routes not cached').($prod ? ' — run "Clear & rebuild caches"' : ' (not needed locally)')],
        ];
    }

    private function pendingMigrations(): ?int
    {
        try {
            $migrator = app('migrator');
            if (! $migrator->repositoryExists()) {
                return null;
            }
            $files = $migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths()));

            return count(array_diff(array_keys($files), $migrator->getRepository()->getRan()));
        } catch (\Throwable) {
            return null;
        }
    }

    private function authorizeMaintenance(): void
    {
        abort_unless(request()->user()->can('system.maintenance'), 403);
    }
}
