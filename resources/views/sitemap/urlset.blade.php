{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach($entries as $e)
    <url>
        <loc>{{ $e['loc'] }}</loc>
@if($e['lastmod'])
        <lastmod>{{ $e['lastmod']->toAtomString() }}</lastmod>
@endif
        <changefreq>{{ $e['changefreq'] }}</changefreq>
        <priority>{{ number_format((float) $e['priority'], 1) }}</priority>
@foreach($e['images'] as $img)
        <image:image>
            <image:loc>{{ $img['loc'] }}</image:loc>
@if(! empty($img['title']))
            <image:title>{{ $img['title'] }}</image:title>
@endif
        </image:image>
@endforeach
    </url>
@endforeach
</urlset>
