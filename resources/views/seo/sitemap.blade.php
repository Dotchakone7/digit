{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('home') }}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
    <url><loc>{{ route('catalog.index') }}</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
@foreach ($categories as $category)
    <url><loc>{{ route('catalog.category', $category->slug) }}</loc><lastmod>{{ $category->updated_at?->toAtomString() }}</lastmod><priority>0.8</priority></url>
@endforeach
@foreach ($products as $product)
    <url><loc>{{ route('products.show', $product->slug) }}</loc><lastmod>{{ $product->updated_at?->toAtomString() }}</lastmod><priority>0.7</priority></url>
@endforeach
@foreach ($pages as $page)
    <url><loc>{{ route('pages.show', $page) }}</loc><priority>0.3</priority></url>
@endforeach
</urlset>
