@php
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle ? $pageTitle.' | '.config('shop.name') : config('shop.name').' — '.config('shop.tagline');
    $description = trim($__env->yieldContent('meta_description')) ?: config('shop.tagline');
    $image = trim($__env->yieldContent('og_image')) ?: (setting('hero_image') ? \App\Support\Media::url(setting('hero_image')) : null);
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
@endphp
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($description), 160) }}">
<link rel="canonical" href="{{ $canonical }}">
@hasSection('robots')<meta name="robots" content="@yield('robots')">@endif
<meta property="og:site_name" content="{{ config('shop.name') }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($description), 200) }}">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:locale" content="fr_FR">
@if ($image)<meta property="og:image" content="{{ $image }}">@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
