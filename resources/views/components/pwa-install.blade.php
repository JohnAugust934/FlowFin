{{--
    Prompt de instalação do PWA (Task 5.1). Aparece só quando o navegador
    sinaliza que o app é instalável e o usuário ainda não dispensou. Inclua
    uma vez no app shell.
--}}
<div
    x-data="pwaInstall"
    x-show="visible"
    x-cloak
    x-transition
    class="fixed inset-x-0 bottom-0 z-40 p-4 sm:inset-x-auto sm:right-6 sm:bottom-6 sm:w-96"
>
    <div class="flex items-center gap-3 rounded-2xl bg-white dark:bg-neutral-800 shadow-xl border border-neutral-200/70 dark:border-white/10 p-3.5">
        <span class="shrink-0 flex items-center justify-center w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-500/15">
            <x-brand-icon class="h-7 w-7" />
        </span>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-neutral-900 dark:text-white">Instalar o FlowFin</p>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 leading-snug">Acesso rápido pela tela inicial do seu celular.</p>
        </div>
        <button
            type="button"
            @click="install()"
            class="btn-primary shrink-0 text-sm"
        >
            Instalar
        </button>
        <button
            type="button"
            @click="dismiss()"
            class="shrink-0 text-neutral-400 hover:text-neutral-600 transition text-xl leading-none px-1"
            aria-label="Agora não"
        >
            &times;
        </button>
    </div>
</div>
