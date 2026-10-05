{{-- Elegant replacement for window.confirm(), driven by $store.confirm. --}}
<div x-data x-show="$store.confirm.open" x-cloak class="fixed inset-0 z-[90] flex items-end justify-center p-4 sm:items-center"
     role="dialog" aria-modal="true" aria-labelledby="confirm-title" @keydown.escape.window="$store.confirm.answer(false)">
    <div x-show="$store.confirm.open" x-transition.opacity class="absolute inset-0 bg-brand-950/40 backdrop-blur-[2px]" @click="$store.confirm.answer(false)"></div>
    <div x-show="$store.confirm.open" x-trap.noscroll="$store.confirm.open"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
         class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
        <div class="flex gap-4">
            <div class="grid size-11 shrink-0 place-items-center rounded-2xl" :class="$store.confirm.danger ? 'bg-danger-50 text-danger-600' : 'bg-brand-50 text-brand-700'">
                <x-icon name="alert" class="size-5" />
            </div>
            <div>
                <h2 id="confirm-title" class="text-lg font-semibold" x-text="$store.confirm.title"></h2>
                <p class="mt-1 text-sm text-zinc-500" x-text="$store.confirm.message"></p>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="btn btn-secondary" @click="$store.confirm.answer(false)">Annuler</button>
            <button type="button" class="btn" :class="$store.confirm.danger ? 'btn-danger' : 'btn-primary'" @click="$store.confirm.answer(true)" x-text="$store.confirm.confirmLabel"></button>
        </div>
    </div>
</div>
