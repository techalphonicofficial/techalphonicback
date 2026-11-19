<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * The public website is the Next.js app in frontend/. Its URLs stay registered here as named routes, because
 * links, breadcrumbs, schema and sitemaps are generated from them; a request that reaches Laravel directly is
 * sent permanently to the same path on FRONTEND_URL, so the backend host never serves duplicate pages.
 */
class FrontendRedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $frontend = rtrim((string) config('services.frontend.url'), '/');
        abort_if($frontend === '', 404);

        $path = '/'.trim($request->path(), '/');
        $qs = $request->getQueryString();

        return redirect()->away($frontend.($path === '/' ? '/' : $path.'/').($qs ? '?'.$qs : ''), 301);
    }
}
