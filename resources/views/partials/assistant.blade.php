<div x-data="assistant('{{ route('assistant.message') }}')" @class(["fixed right-4 z-[60] sm:right-6", "bottom-24 lg:bottom-6" => $hasStickyBar ?? false, "bottom-4 sm:bottom-6" => ! ($hasStickyBar ?? false)])>
    <div x-show="open" x-cloak x-transition.origin.bottom.right
         class="mb-3 flex h-[28rem] w-[min(22rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-zinc-900/5"
         role="dialog" aria-label="Assistant">
        <div class="flex items-center justify-between bg-brand-900 px-4 py-3 text-white">
            <p class="flex items-center gap-2 text-sm font-semibold"><x-icon name="sparkles" class="size-4 text-accent-400" /> Assistant {{ config('shop.name') }}</p>
            <button type="button" class="rounded-lg p-1 text-white/70 hover:bg-white/10 hover:text-white" @click="open = false" aria-label="Fermer"><x-icon name="x" class="size-4" /></button>
        </div>
        <div x-ref="log" class="flex-1 space-y-3 overflow-y-auto bg-canvas p-4" aria-live="polite">
            <template x-for="(m, i) in messages" :key="i">
                <div :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div class="max-w-[85%] rounded-2xl px-3.5 py-2.5 text-sm" :class="m.role === 'user' ? 'bg-brand-900 text-white rounded-br-md' : 'bg-white text-zinc-700 ring-1 ring-zinc-100 rounded-bl-md'">
                        <p x-text="m.text"></p>
                        <template x-for="l in m.links ?? []" :key="l.url">
                            <a :href="l.url" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-accent-700" x-text="l.label + ' →'"></a>
                        </template>
                    </div>
                </div>
            </template>
            <div x-show="busy" class="flex gap-1 px-2"><span class="size-2 animate-bounce rounded-full bg-zinc-300"></span><span class="size-2 animate-bounce rounded-full bg-zinc-300 [animation-delay:120ms]"></span><span class="size-2 animate-bounce rounded-full bg-zinc-300 [animation-delay:240ms]"></span></div>
        </div>
        <form class="flex gap-2 border-t border-zinc-100 p-3" @submit.prevent="send">
            <label for="assistant-input" class="sr-only">Votre question</label>
            <input id="assistant-input" x-model="input" maxlength="500" class="input rounded-full" placeholder="Où est ma commande ?">
            <button type="submit" class="btn-icon shrink-0 bg-brand-900 text-white hover:bg-brand-800" :disabled="busy" aria-label="Envoyer"><x-icon name="send" class="size-4" /></button>
        </form>
    </div>
    <button type="button" @click="open = !open" class="ml-auto grid size-14 place-items-center rounded-full bg-brand-900 text-white shadow-xl transition hover:scale-105 hover:bg-brand-800" :aria-expanded="open.toString()" aria-label="Ouvrir l’assistant">
        <x-icon name="chat" class="size-6" />
    </button>
</div>
