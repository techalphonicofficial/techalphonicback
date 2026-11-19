{{-- Dependency-free error page (used for 500/503 when the database or cache may be unavailable). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
    <title>{{ $title }}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;background:#0B0D12;color:#fff;display:grid;place-items:center;min-height:100vh;padding:1.5rem;text-align:center}
        .sq{display:inline-grid;grid-template-columns:repeat(2,14px);gap:6px;margin-bottom:2rem}.sq span{width:14px;height:14px;border-radius:3px}
        h1{font-size:clamp(4rem,12vw,7rem);line-height:1;margin:0;font-weight:800;background:linear-gradient(100deg,#FE5C36,#FEB449,#53AFBE);-webkit-background-clip:text;background-clip:text;color:transparent}
        p{color:rgba(255,255,255,.7);max-width:32rem;margin:1rem auto;line-height:1.6}a{display:inline-block;margin-top:1rem;background:#FE5C36;color:#fff;padding:.85rem 1.5rem;border-radius:.75rem;font-weight:600;text-decoration:none}
    </style>
</head>
<body>
<main>
    <div class="sq" aria-hidden="true"><span style="background:#53AFBE"></span><span style="background:#FE5C36"></span><span style="background:#FEB449"></span><span style="background:#7C7C7C"></span></div>
    <h1>{{ $code }}</h1>
    <p>{{ $message }}</p>
    <a href="/">Back to homepage</a>
</main>
</body>
</html>
