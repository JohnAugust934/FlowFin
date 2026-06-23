{{--
    Container de toasts (feedback visual imediato). Inclua-o UMA vez no app shell.

    Disparo no front-end (qualquer lugar com Alpine):
        $dispatch('toast', { type: 'success', message: 'Transação registrada ✓' })
    ou via JS puro:
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Algo deu errado' } }))

    Tipos suportados: success | error | warning.
    Também exibe automaticamente as flash messages da sessão (success/error/warning/status).
--}}
<div
    x-data="flowfinToasts()"
    @toast.window="push($event.detail)"
    class="fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4 pointer-events-none sm:items-end sm:top-6 sm:right-6 sm:left-auto sm:px-0"
    aria-live="polite"
    aria-atomic="true"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-[-12px] sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto w-full max-w-sm flex items-start gap-3 rounded-xl bg-white shadow-lg border px-4 py-3"
            :class="{
                'border-success/30': toast.type === 'success',
                'border-danger/30': toast.type === 'error',
                'border-warning/30': toast.type === 'warning',
            }"
            role="status"
        >
            {{-- Ícone por tipo --}}
            <span
                class="shrink-0 mt-0.5 flex items-center justify-center w-5 h-5 rounded-full text-white text-xs"
                :class="{
                    'bg-success': toast.type === 'success',
                    'bg-danger': toast.type === 'error',
                    'bg-warning': toast.type === 'warning',
                }"
            >
                <span x-show="toast.type === 'success'">&check;</span>
                <span x-show="toast.type === 'error'">&times;</span>
                <span x-show="toast.type === 'warning'">!</span>
            </span>

            <p class="flex-1 text-sm text-neutral-700 leading-snug" x-text="toast.message"></p>

            <button
                type="button"
                @click="dismiss(toast.id)"
                class="shrink-0 text-neutral-400 hover:text-neutral-600 transition"
                aria-label="Fechar aviso"
            >
                &times;
            </button>
        </div>
    </template>
</div>

@once
    @push('scripts')
        <script>
            function flowfinToasts() {
                return {
                    toasts: [],
                    push(detail) {
                        const id = Date.now() + Math.random();
                        const toast = {
                            id,
                            type: detail.type || 'success',
                            message: detail.message || '',
                            visible: true,
                        };
                        this.toasts.push(toast);
                        // Remoção automática após 4s.
                        setTimeout(() => this.dismiss(id), detail.duration || 4000);
                    },
                    dismiss(id) {
                        const toast = this.toasts.find((t) => t.id === id);
                        if (!toast) return;
                        toast.visible = false;
                        setTimeout(() => {
                            this.toasts = this.toasts.filter((t) => t.id !== id);
                        }, 250);
                    },
                    init() {
                        // Exibe flash messages da sessão ao carregar a página.
                        @if (session('success'))
                            this.push({ type: 'success', message: @json(session('success')) });
                        @endif
                        @if (session('status'))
                            this.push({ type: 'success', message: @json(session('status')) });
                        @endif
                        @if (session('error'))
                            this.push({ type: 'error', message: @json(session('error')) });
                        @endif
                        @if (session('warning'))
                            this.push({ type: 'warning', message: @json(session('warning')) });
                        @endif
                    },
                };
            }
        </script>
    @endpush
@endonce
