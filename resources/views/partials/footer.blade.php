@php
    $socials = array_filter(config('shop.social'));
    $socialIcons = ['facebook' => 'facebook', 'instagram' => 'instagram', 'tiktok' => 'tiktok', 'x' => 'x-social', 'youtube' => 'youtube', 'linkedin' => 'linkedin'];
@endphp
<footer class="mt-24 bg-brand-950 text-brand-200">
    <div class="container-shop py-16">
        <div class="grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <x-logo variant="light" />
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-brand-300">{{ setting('about_text', config('shop.tagline')) }}</p>
                @if ($socials)
                    <div class="mt-6 flex gap-2">
                        @foreach ($socials as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="grid size-10 place-items-center rounded-full bg-white/5 text-brand-100 transition hover:bg-white/10 hover:text-white" aria-label="{{ ucfirst($network) }}">
                                <x-icon :name="$socialIcons[$network] ?? 'external'" class="size-[18px]" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:col-span-8">
                <div>
                    <h2 class="font-sans text-sm font-semibold text-white">Boutique</h2>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ route('catalog.index') }}" class="transition hover:text-white">Tous les produits</a></li>
                        <li><a href="{{ route('catalog.index', ['sort' => 'newest']) }}" class="transition hover:text-white">Nouveautés</a></li>
                        <li><a href="{{ route('catalog.index', ['on_sale' => 1]) }}" class="transition hover:text-white">Promotions</a></li>
                        @foreach ($navCategories->take(4) as $category)
                            <li><a href="{{ route('catalog.category', $category['slug']) }}" class="transition hover:text-white">{{ $category['name'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h2 class="font-sans text-sm font-semibold text-white">Aide & informations</h2>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @foreach (\App\Http\Controllers\Shop\PageController::PAGES as $slug => $page)
                            <li><a href="{{ route('pages.show', $slug) }}" class="transition hover:text-white">{{ $page['title'] }}</a></li>
                        @endforeach
                        <li><a href="{{ route('contact') }}" class="transition hover:text-white">Nous contacter</a></li>
                    </ul>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <h2 class="font-sans text-sm font-semibold text-white">Contact</h2>
                    @unless (setting('contact_phone') || setting('contact_whatsapp') || setting('contact_email') || setting('contact_address'))
                        <p class="mt-4 text-sm"><a href="{{ route('contact') }}" class="transition hover:text-white">Nous écrire</a></p>
                    @endunless
                    <ul class="mt-4 space-y-3 text-sm">
                        @if ($phone = setting('contact_phone'))
                            <li class="flex gap-2.5"><x-icon name="phone" class="mt-0.5 size-4 text-brand-400" /><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="hover:text-white">{{ $phone }}</a></li>
                        @endif
                        @if ($whatsapp = setting('contact_whatsapp'))
                            <li class="flex gap-2.5"><x-icon name="whatsapp" class="mt-0.5 size-4 text-brand-400" /><a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" target="_blank" rel="noopener" class="hover:text-white">WhatsApp</a></li>
                        @endif
                        @if ($email = setting('contact_email'))
                            <li class="flex gap-2.5"><x-icon name="mail" class="mt-0.5 size-4 text-brand-400" /><a href="mailto:{{ $email }}" class="break-all hover:text-white">{{ $email }}</a></li>
                        @endif
                        @if ($address = setting('contact_address'))
                            <li class="flex gap-2.5"><x-icon name="map-pin" class="mt-0.5 size-4 text-brand-400" /><span>{{ $address }}</span></li>
                        @endif
                        @if ($hours = setting('opening_hours'))
                            <li class="flex gap-2.5"><x-icon name="clock" class="mt-0.5 size-4 text-brand-400" /><span>{{ $hours }}</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-shop flex flex-col items-center justify-between gap-3 py-6 text-xs text-brand-400 sm:flex-row">
            <p>© {{ date('Y') }} {{ config('shop.name') }}. Tous droits réservés.</p>
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1.5"><x-icon name="lock" class="size-3.5" /> Paiement sécurisé</span>
                <span aria-hidden="true">·</span>
                <span>Orange Money · MTN MoMo · Moov · Wave</span>
            </div>
        </div>
    </div>
</footer>
