import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import { registerStores } from './stores';
import { enhanceForms } from './components';

Alpine.plugin(collapse);
Alpine.plugin(focus);
registerStores(Alpine);
enhanceForms(Alpine);

/** Repeater for key/value product specifications and variants. */
Alpine.data('repeater', (rows = [], blank = {}) => ({
    rows: rows.length ? rows : [],
    add() {
        this.rows.push({ ...blank, _key: Date.now() + Math.random() });
    },
    remove(index) {
        this.rows.splice(index, 1);
    },
}));

/** Slug preview generated from the name field (the server re-validates it). */
Alpine.data('slugger', (name = '', slug = '', locked = false) => ({
    name,
    slug,
    locked,
    sync() {
        if (this.locked) return;
        this.slug = this.name
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    },
}));

window.Alpine = Alpine;
Alpine.start();

document.querySelectorAll('[data-flash-toast]').forEach((el) => {
    Alpine.store('toast').push(el.dataset.message, el.dataset.type ?? 'success', 5000);
});
