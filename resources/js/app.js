import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import { registerStores } from './stores';
import { registerComponents, registerCartPage, enhanceForms } from './components';

Alpine.plugin(collapse);
Alpine.plugin(focus);

registerStores(Alpine);
registerComponents(Alpine);
registerCartPage(Alpine);
enhanceForms(Alpine);

window.Alpine = Alpine;
Alpine.start();

// Server-side flash messages → toasts.
document.querySelectorAll('[data-flash-toast]').forEach((el) => {
    Alpine.store('toast').push(el.dataset.message, el.dataset.type ?? 'success', 5000);
});
