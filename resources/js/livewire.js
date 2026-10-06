/**
 * Single entry point for Livewire + Alpine. Livewire ships its own Alpine
 * (with collapse, focus, intersect… plugins): importing it from here
 * guarantees one Alpine instance shared by Livewire components and our
 * own Alpine stores/components.
 */
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { registerStores } from './stores';
import { enhanceForms } from './components';

export function boot(register = () => {}) {
    registerStores(Alpine);
    register(Alpine);
    enhanceForms(Alpine);

    // Server flash messages → toasts (first load and every wire:navigate).
    document.addEventListener('livewire:navigated', () => {
        document.querySelectorAll('[data-flash-toast]:not([data-shown])').forEach((el) => {
            el.dataset.shown = '1';
            Alpine.store('toast').push(el.dataset.message, el.dataset.type ?? 'success', 5000);
        });
    });

    // Overlays must not stay open on the next page.
    document.addEventListener('livewire:navigating', () => {
        Alpine.store('cart').open = false;
        Alpine.store('confirm').open = false;
    });

    // Livewire components call $this->dispatch('toast', message: '…', type: 'success').
    window.addEventListener('toast', (event) => {
        Alpine.store('toast').push(event.detail.message, event.detail.type ?? 'success');
    });

    // A failed Livewire request (expired session, server error) gets a readable message.
    Livewire.interceptRequest(({ onError }) => {
        onError(({ response, preventDefault }) => {
            const status = response?.status;
            if (status === 419) {
                preventDefault();
                Alpine.store('toast').push('Votre session a expiré. La page va se recharger.', 'error');
                setTimeout(() => window.location.reload(), 1500);
            } else if (status === 403 || status >= 500) {
                preventDefault();
                Alpine.store('toast').push(
                    status === 403 ? 'Action non autorisée.' : 'Une erreur est survenue. Veuillez réessayer.',
                    'error',
                );
            }
        });
    });

    window.Alpine = Alpine;
    Livewire.start();
}
