<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · {{ setting('site_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..700&display=swap">
    @vite(['resources/css/admin.css'])
</head>
<body class="min-h-screen bg-white">
<div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
    {{-- Brand panel --}}
    <aside class="relative hidden overflow-hidden bg-night p-12 text-white lg:flex lg:flex-col">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -left-32 -top-32 size-[34rem] rounded-full bg-teal/25 blur-3xl"></div>
            <div class="absolute -bottom-40 right-0 size-[30rem] rounded-full bg-amber/10 blur-3xl"></div>
            <div class="absolute inset-0 [background-image:linear-gradient(to_right,rgb(255_255_255/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(255_255_255/0.04)_1px,transparent_1px)] [background-size:56px_56px] [mask-image:radial-gradient(ellipse_at_top_left,black,transparent_75%)]"></div>
        </div>
        <div class="relative flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl bg-white/[0.06] ring-1 ring-white/10">
                <span class="grid grid-cols-2 gap-1" aria-hidden="true"><span class="size-2 rounded-[2px] bg-teal"></span><span class="size-2 rounded-[2px] bg-coral"></span><span class="size-2 rounded-[2px] bg-amber"></span><span class="size-2 rounded-[2px] bg-steel"></span></span>
            </span>
            <span class="text-base font-semibold">{{ setting('site_name') }}</span>
        </div>

        <div class="relative mt-auto max-w-md">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal">Admin console</p>
            <h2 class="mt-4 text-[2.25rem] font-semibold leading-[1.1] tracking-[-0.03em] text-white">Run the website, the content and the pipeline from one place.</h2>
            <ul class="mt-8 grid gap-3 text-sm text-slate-300">
                @foreach(['Pages, services and programmatic SEO at scale', 'Leads with assignment, notes and export', 'Redirects, schema and sitemaps built in'] as $point)
                    <li class="flex items-center gap-3"><span class="grid size-5 place-items-center rounded-full bg-teal/15 text-teal ring-1 ring-teal/30"><x-icon name="check" class="size-3" /></span>{{ $point }}</li>
                @endforeach
            </ul>
        </div>
        <p class="relative mt-12 text-xs text-slate-500">© {{ now()->year }} {{ setting('site_name') }}</p>
    </aside>

    {{-- Form --}}
    <main class="flex items-center justify-center bg-canvas p-6 sm:p-10 lg:bg-white">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex items-center gap-2.5 lg:hidden">
                <span class="grid grid-cols-2 gap-1" aria-hidden="true"><span class="size-2.5 rounded-sm bg-teal"></span><span class="size-2.5 rounded-sm bg-coral"></span><span class="size-2.5 rounded-sm bg-amber"></span><span class="size-2.5 rounded-sm bg-steel"></span></span>
                <span class="font-semibold text-ink">{{ setting('site_name') }}</span>
            </div>

            <h1 class="text-2xl font-semibold tracking-[-0.025em] text-ink">Welcome back</h1>
            <p class="mt-1.5 text-sm text-slate-500">Sign in to continue to the admin console.</p>

            @if($errors->any())
                <div class="a-alert mt-6 border-red-200 bg-red-50 text-red-800" role="alert"><x-icon name="alert-triangle" class="mt-0.5 size-4 shrink-0" /><span>{{ $errors->first() }}</span></div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="mt-7 grid gap-4">
                @csrf
                <div>
                    <label for="email" class="a-label">Email address</label>
                    <label class="a-input-icon block"><x-icon name="mail" /><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@company.com" class="a-input min-h-10"></label>
                </div>
                <div>
                    <label for="password" class="a-label">Password</label>
                    <div class="relative">
                        <label class="a-input-icon block"><x-icon name="shield" /><input id="password" type="password" name="password" required autocomplete="current-password" class="a-input min-h-10 pr-16"></label>
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-medium text-slate-500 hover:text-ink" aria-controls="password"
                                onclick="const p = document.getElementById('password'); const show = p.type === 'password'; p.type = show ? 'text' : 'password'; this.textContent = show ? 'Hide' : 'Show';">Show</button>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-[0.8125rem] text-slate-600"><input type="checkbox" name="remember" value="1" class="a-check"> Keep me signed in</label>
                <button class="a-btn a-btn-primary mt-1 min-h-10 w-full">Sign in <x-icon name="arrow-right" class="size-4" /></button>
            </form>

            <p class="mt-8 flex items-center justify-between text-xs text-slate-500">
                <a href="{{ url('/') }}" class="hover:text-ink">← Back to website</a>
                <span class="flex items-center gap-1.5"><x-icon name="shield" class="size-3.5" /> Secured admin area</span>
            </p>
        </div>
    </main>
</div>
</body>
</html>
