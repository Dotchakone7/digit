import { http } from './http';

export function registerComponents(Alpine) {
    const toast = (m, t) => Alpine.store('toast').push(m, t);

    /** Header instant search with keyboard navigation. */
    Alpine.data('searchBox', (initial = '') => ({
        q: initial,
        open: false,
        loading: false,
        results: { products: [], categories: [], did_you_mean: null },
        active: -1,
        controller: null,
        get flat() {
            return [...this.results.categories, ...this.results.products];
        },
        async search() {
            if (this.q.trim().length < 2) {
                this.open = false;
                return;
            }
            this.controller?.abort();
            this.loading = true;
            this.open = true;
            try {
                this.results = await http(`/recherche/suggestions?q=${encodeURIComponent(this.q.trim())}`);
                this.active = -1;
            } catch {
                this.results = { products: [], categories: [], did_you_mean: null };
            } finally {
                this.loading = false;
            }
        },
        move(step) {
            if (!this.flat.length) return;
            this.active = (this.active + step + this.flat.length) % this.flat.length;
        },
        submit(event) {
            if (this.active >= 0 && this.flat[this.active]) {
                event.preventDefault();
                window.location.href = this.flat[this.active].url;
            }
        },
    }));

    /** Product page: variant choice, quantity and add-to-cart. */
    Alpine.data('productPurchase', (config) => ({
        variants: config.variants ?? [],
        variantId: config.variants?.length === 1 ? config.variants[0].id : null,
        quantity: 1,
        busy: false,
        get variant() {
            return this.variants.find((v) => v.id === this.variantId) ?? null;
        },
        get stock() {
            return this.variant ? this.variant.stock : config.stock;
        },
        get price() {
            return this.variant?.price ?? config.price;
        },
        get needsVariant() {
            return this.variants.length > 0 && !this.variant;
        },
        get maxQuantity() {
            return Math.max(1, Math.min(this.stock, config.maxPerLine));
        },
        clamp() {
            this.quantity = Math.min(Math.max(1, parseInt(this.quantity) || 1), this.maxQuantity);
        },
        async add(redirect = false) {
            if (this.needsVariant) {
                toast('Veuillez choisir une option.', 'error');
                return;
            }
            this.busy = true;
            try {
                const data = await Alpine.store('cart').add({
                    product_id: config.productId,
                    variant_id: this.variantId,
                    quantity: this.quantity,
                });
                if (redirect) {
                    window.location.href = config.checkoutUrl;
                    return;
                }
                toast(data.message);
                Alpine.store('cart').open = true;
            } catch (e) {
                toast(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
    }));

    /** Product image gallery with hover zoom (desktop) and swipe-friendly thumbs. */
    Alpine.data('gallery', (images) => ({
        images,
        index: 0,
        zoom: false,
        origin: '50% 50%',
        lightbox: false,
        get current() {
            return this.images[this.index] ?? null;
        },
        go(i) {
            this.index = (i + this.images.length) % this.images.length;
        },
        track(e) {
            const r = e.currentTarget.getBoundingClientRect();
            this.origin = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`;
        },
    }));

    /** Quick "add to cart" button on product cards (products without variants). */
    Alpine.data('quickAdd', (productId) => ({
        busy: false,
        async add() {
            this.busy = true;
            try {
                const data = await Alpine.store('cart').add({ product_id: productId, quantity: 1 });
                toast(data.message);
            } catch (e) {
                toast(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
    }));

    Alpine.data('wishlistButton', (url, active, authenticated) => ({
        active,
        busy: false,
        async toggle() {
            if (!authenticated) {
                toast('Connectez-vous pour enregistrer vos favoris.', 'info');
                setTimeout(() => (window.location.href = '/connexion'), 900);
                return;
            }
            this.busy = true;
            try {
                const data = await http(url, { method: 'POST' });
                this.active = data.active;
                toast(data.message);
            } catch (e) {
                toast(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
    }));

    /** Cart line quantity control (cart page & drawer). */
    Alpine.data('cartLine', (id, quantity, max) => ({
        quantity,
        max,
        busy: false,
        async set(value) {
            const next = Math.min(Math.max(0, value), this.max);
            if (next === this.quantity && next !== 0) return;
            if (next === 0) return this.remove();
            this.busy = true;
            try {
                await Alpine.store('cart').update(id, next);
                this.quantity = next;
                this.$dispatch('cart-updated');
            } catch (e) {
                toast(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
        async remove() {
            const ok = await Alpine.store('confirm').ask({
                title: 'Retirer cet article ?',
                message: 'Il sera supprimé de votre panier.',
                confirmLabel: 'Retirer',
            });
            if (!ok) return;
            this.busy = true;
            try {
                const data = await Alpine.store('cart').remove(id);
                toast(data.message);
                this.$dispatch('cart-updated');
            } catch (e) {
                toast(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
    }));

    Alpine.data('share', (url, title) => ({
        async share() {
            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                } catch {
                    /* user cancelled */
                }
                return;
            }
            await navigator.clipboard?.writeText(url);
            toast('Lien copié dans le presse-papiers.');
        },
    }));

    Alpine.data('assistant', (url) => ({
        open: false,
        busy: false,
        input: '',
        messages: [{ role: 'assistant', text: 'Bonjour 👋 Comment puis-je vous aider ?', links: [] }],
        async send() {
            const text = this.input.trim();
            if (!text) return;
            this.messages.push({ role: 'user', text, links: [] });
            this.input = '';
            this.busy = true;
            try {
                const data = await http(url, { method: 'POST', body: { message: text } });
                this.messages.push({ role: 'assistant', ...data });
            } catch (e) {
                this.messages.push({ role: 'assistant', text: e.message, links: [] });
            } finally {
                this.busy = false;
                this.$nextTick(() => this.$refs.log?.scrollTo({ top: 1e6, behavior: 'smooth' }));
            }
        },
    }));
}

/**
 * Any <form data-confirm="..."> asks for confirmation in the elegant modal
 * before submitting; buttons with data-loading show a spinner after submit.
 */
export function enhanceForms(Alpine) {
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.dataset.confirm && !form.dataset.confirmed) {
            event.preventDefault();
            const ok = await Alpine.store('confirm').ask({
                title: form.dataset.confirmTitle ?? 'Confirmer l’action',
                message: form.dataset.confirm,
                confirmLabel: form.dataset.confirmLabel ?? 'Confirmer',
                danger: form.dataset.confirmTone !== 'neutral',
            });
            if (ok) {
                form.dataset.confirmed = '1';
                form.requestSubmit(event.submitter ?? undefined);
            }
            return;
        }

        const button = event.submitter ?? form.querySelector('[type="submit"]');
        if (button?.dataset.loading !== undefined && !event.defaultPrevented) {
            // Disable on the next tick so a named submitter's value is still sent.
            setTimeout(() => (button.disabled = true));
            button.dataset.originalHtml = button.innerHTML;
            button.innerHTML = `<span class="spinner"></span><span>${button.dataset.loading || 'Patientez…'}</span>`;
        }
    });

    // Restore buttons when the page is shown from the bfcache (back button).
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-original-html]').forEach((b) => {
            b.innerHTML = b.dataset.originalHtml;
            b.disabled = false;
        });
    });
}

export function registerCartPage(Alpine) {
    Alpine.data('couponForm', () => ({
        code: '',
        busy: false,
        async apply() {
            if (!this.code.trim()) return;
            this.busy = true;
            try {
                const data = await http('/panier/code-promo', { method: 'POST', body: { code: this.code.trim() } });
                Alpine.store('cart').summary = data.cart;
                Alpine.store('toast').push(data.message);
                this.code = '';
            } catch (e) {
                Alpine.store('toast').push(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
        async remove() {
            this.busy = true;
            try {
                const data = await http('/panier/code-promo', { method: 'DELETE' });
                Alpine.store('cart').summary = data.cart;
                Alpine.store('toast').push(data.message);
            } catch (e) {
                Alpine.store('toast').push(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
    }));
}
