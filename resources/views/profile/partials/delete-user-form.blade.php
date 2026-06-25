<section x-data="accountDeletion" @keydown.escape.window="close()">
    <header>
        <h2 class="text-base font-semibold text-danger">Excluir minha conta</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            Ao excluir sua conta, <strong class="text-neutral-700 dark:text-neutral-200">todos os seus dados são apagados para sempre</strong> e não há como recuperar. Se quiser guardar algo, baixe seus dados antes.
        </p>
    </header>

    <button type="button" @click="open = true" class="btn-danger mt-5">Excluir minha conta</button>

    {{-- Modal de confirmação --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div x-show="open" x-transition.opacity @click="close()" class="absolute inset-0 bg-neutral-900/60 dark:bg-black/70 backdrop-blur-sm"></div>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="glass relative z-10 w-full max-w-md p-6 bg-white/95 dark:bg-neutral-900/95">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">Tem certeza que quer excluir sua conta?</h3>
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                    Esta ação é <strong class="text-danger">definitiva e irreversível</strong>. Para confirmar, digite sua senha atual.
                </p>

                <form @submit.prevent="confirm()" class="mt-5 space-y-3">
                    <div>
                        <label for="delete_account_password" class="sr-only">Senha atual</label>
                        <input id="delete_account_password" type="password" x-model="password" autocomplete="current-password"
                               placeholder="Sua senha atual" x-ref="pwd"
                               class="block w-full border-neutral-300 rounded-lg focus:border-danger focus:ring-danger"
                               :class="{ '!border-danger': error }">
                        <p x-show="error" x-text="error" class="text-sm text-danger mt-1.5"></p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="close()" :disabled="deleting" class="btn-secondary">Cancelar</button>
                        <button type="submit" :disabled="deleting || !password" class="btn-danger">
                            <span x-show="!deleting">Excluir definitivamente</span>
                            <span x-show="deleting" x-cloak>Excluindo…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</section>
