import { http } from './http';

export function registerStores(Alpine) {
    Alpine.store('toast', {
        items: [],
        push(message, type = 'success', timeout = 4000) {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, type });
            setTimeout(() => this.dismiss(id), timeout);
        },
        dismiss(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },
    });

    Alpine.store('cart', {
        count: Number(document.querySelector('meta[name="cart-count"]')?.content ?? 0),
        open: false,
        loading: false,
        summary: null,
        async refresh() {
            this.loading = true;
            try {
                this.summary = await http('/panier/resume');
                this.count = this.summary.count;
            } catch (e) {
                Alpine.store('toast').push(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        show() {
            this.open = true;
            this.refresh();
        },
        async add(payload) {
            const data = await http('/panier/articles', { method: 'POST', body: payload });
            this.summary = data.cart;
            this.count = data.cart.count;
            return data;
        },
        async update(id, quantity) {
            const data = await http(`/panier/articles/${id}`, { method: 'PATCH', body: { quantity } });
            this.summary = data.cart;
            this.count = data.cart.count;
            return data;
        },
        async remove(id) {
            const data = await http(`/panier/articles/${id}`, { method: 'DELETE' });
            this.summary = data.cart;
            this.count = data.cart.count;
            return data;
        },
    });

    Alpine.store('confirm', {
        open: false,
        title: '',
        message: '',
        confirmLabel: 'Confirmer',
        danger: true,
        resolve: null,
        ask({ title = 'Êtes-vous sûr ?', message = '', confirmLabel = 'Confirmer', danger = true } = {}) {
            Object.assign(this, { title, message, confirmLabel, danger, open: true });
            return new Promise((resolve) => (this.resolve = resolve));
        },
        answer(value) {
            this.open = false;
            this.resolve?.(value);
        },
    });
}
