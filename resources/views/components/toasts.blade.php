{{-- Global toast stack + server flash messages. --}}
<div class="pointer-events-none fixed inset-x-0 bottom-4 z-[100] flex flex-col items-center gap-2 px-4 sm:top-5 sm:right-5 sm:bottom-auto sm:left-auto sm:items-end"
     x-data aria-live="polite" aria-atomic="false">
    <template x-for="toast in $store.toast.items" :key="toast.id">
        <div class="pointer-events-auto flex w-full max-w-sm animate-toast-in items-start gap-3 rounded-2xl bg-brand-950/95 p-4 pr-3 text-sm text-white shadow-2xl ring-1 ring-white/10 backdrop-blur"
             role="status">
            <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full"
                  :class="{ 'bg-success-500': toast.type === 'success', 'bg-danger-500': toast.type === 'error', 'bg-info-500': toast.type === 'info' }">
                <svg x-show="toast.type === 'success'" class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                <svg x-show="toast.type !== 'success'" class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M12 7v6M12 17h.01"/></svg>
            </span>
            <p class="flex-1 leading-snug" x-text="toast.message"></p>
            <button type="button" class="-my-1 rounded-lg p-1 text-white/60 transition hover:bg-white/10 hover:text-white" @click="$store.toast.dismiss(toast.id)" aria-label="Fermer">
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    </template>
</div>

@if (session('toast'))
    <span hidden data-flash-toast data-type="{{ session('toast.type', 'success') }}" data-message="{{ session('toast.message') }}"></span>
@endif
@if (session('status'))
    <span hidden data-flash-toast data-type="success" data-message="{{ session('status') }}"></span>
@endif
@if ($errors->any() && ! session('toast'))
    <span hidden data-flash-toast data-type="error" data-message="Veuillez corriger les champs signalés."></span>
@endif
