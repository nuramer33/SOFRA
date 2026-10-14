<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    @foreach ([route('home'), route('legal.privacy'), route('legal.terms')] as $page)
        <url><loc>{{ $page }}</loc></url>
    @endforeach
    @foreach ($shops as $shop)
        @php($locales = $shop->contentLocales())
        <url>
            <loc>{{ route('menu.show', $shop) }}</loc>
            <lastmod>{{ $shop->updated_at->toAtomString() }}</lastmod>
            @if (count($locales) > 1)
                @foreach ($locales as $locale)
                    <xhtml:link rel="alternate" hreflang="{{ $locale }}" href="{{ route('menu.show', [$shop, 'lang' => $locale]) }}"/>
                @endforeach
                <xhtml:link rel="alternate" hreflang="x-default" href="{{ route('menu.show', $shop) }}"/>
            @endif
        </url>
    @endforeach
</urlset>