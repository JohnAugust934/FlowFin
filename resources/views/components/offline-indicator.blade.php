{{--
    Indicador global de conexão/sincronização (Task 5.2).
    Só aparece quando há algo a comunicar: app offline, transações pendentes
    ou sincronização em andamento. Inclua uma vez no app shell.
--}}
<div
    x-data="offlineSync"
    x-show="visible"
    x-cloak
    x-transition.opacity
    class="fixed z-40 left-1/2 -translate-x-1/2 bottom-24 sm:bottom-6 sm:left-auto sm:right-6 sm:translate-x-0"
    role="status"
    aria-live="polite"
>
    <div
        class="flex items-center gap-2.5 rounded-full pl-3 pr-3 py-2 shadow-lg border text-sm font-medium"
        :class="{
            'bg-warning-light text-warning-dark border-warning/30': tone === 'offline' || tone === 'pending',
            'bg-brand-50 text-brand-700 border-brand-500/20 dark:bg-brand-500/15 dark:text-brand-200': tone === 'sync',
        }"
    >
        <span class="relative flex h-2.5 w-2.5 shrink-0">
            <span
                x-show="tone === 'sync'"
                class="absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping bg-brand-500"
            ></span>
            <span
                class="relative inline-flex h-2.5 w-2.5 rounded-full"
                :class="{
                    'bg-warning': tone === 'offline' || tone === 'pending',
                    'bg-brand-500': tone === 'sync',
                }"
            ></span>
        </span>

        <span x-text="label"></span>

        <button
            type="button"
            x-show="canSyncNow"
            @click="syncNow()"
            class="ml-1 rounded-full bg-white/70 dark:bg-white/10 px-2.5 py-0.5 text-xs font-semibold hover:bg-white transition"
        >
            Sincronizar agora
        </button>
    </div>
</div>
