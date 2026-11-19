<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * 1. Admin-managed 301/302/410 redirects (cached map, O(1) lookup).
 * 2. Canonical trailing slash for public GET pages: /foo → /foo/
 */
class HandleRedirects
{
    private const SKIP_PREFIXES = ['api', 'storage', 'build', 'livewire', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = $request->path();
        $first = explode('/', $path)[0];
        if (in_array($first, self::SKIP_PREFIXES, true) || $first === config('site.admin_path')) {
            return $next($request);
        }

        $key = Redirect::normalizePath('/'.$path);
        $map = Redirect::activeMap();

        if (isset($map[$key])) {
            [$target, $code, $id] = $map[$key];
            DB::table('redirects')->where('id', $id)->update(['hits' => DB::raw('hits + 1'), 'last_hit_at' => now()]);

            if ($code === 410) {
                abort(410);
            }
            $qs = $request->getQueryString();

            return redirect()->to(slash_url((string) $target).($qs ? '?'.$qs : ''), $code);
        }

        // Trailing slash canonicalisation (skip files like sitemap.xml / robots.txt and the homepage).
        if (config('site.force_trailing_slash') && $path !== '/' && ! str_ends_with($request->getPathInfo(), '/') && ! str_contains(basename($path), '.')) {
            $qs = $request->getQueryString();

            return redirect()->to(slash_url($path).($qs ? '?'.$qs : ''), 301);
        }

        return $next($request);
    }
}
