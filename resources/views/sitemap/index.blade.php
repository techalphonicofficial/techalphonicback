{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($sections as $section => $lastmod)
    <sitemap>
        <loc>{{ route('sitemap.section', $section) }}</loc>
@if($lastmod)
        <lastmod>{{ $lastmod->toAtomString() }}</lastmod>
@endif
    </sitemap>
@endforeach
</sitemapindex>
