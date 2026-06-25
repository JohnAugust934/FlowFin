<section x-data="exportData">
    <header>
        <h2 class="text-base font-semibold text-neutral-800 dark:text-neutral-100">Relatórios e seus dados</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            Baixe um relatório do mês ou uma cópia completa de tudo o que você registrou.
        </p>
    </header>

    {{-- Relatório do mês (CSV/PDF) --}}
    <div class="mt-6 space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="export_month" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Mês do relatório</label>
                <input id="export_month" type="month" x-model="month" :max="maxMonth"
                       class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <span class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Formato</span>
                <div class="flex gap-2">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" value="csv" x-model="format" class="sr-only peer">
                        <span class="block text-center text-sm font-medium px-3 py-2 rounded-lg border border-neutral-300 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300 peer-checked:bg-brand-50 peer-checked:border-brand-500 peer-checked:text-brand-700 dark:peer-checked:bg-brand-500/15 dark:peer-checked:text-brand-200 transition">
                            Planilha (CSV)
                        </span>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" value="pdf" x-model="format" class="sr-only peer">
                        <span class="block text-center text-sm font-medium px-3 py-2 rounded-lg border border-neutral-300 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300 peer-checked:bg-brand-50 peer-checked:border-brand-500 peer-checked:text-brand-700 dark:peer-checked:bg-brand-500/15 dark:peer-checked:text-brand-200 transition">
                            PDF
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <button type="button" @click="downloadMonthly()" :disabled="downloading || !month" class="btn-primary w-full sm:w-auto">
            <span x-show="!downloading">Baixar relatório do mês</span>
            <span x-show="downloading" x-cloak>Preparando…</span>
        </button>
    </div>

    <div class="my-6 border-t border-neutral-200/70 dark:border-white/10"></div>

    {{-- Export completo (LGPD — portabilidade) --}}
    <div class="space-y-3">
        <div>
            <h3 class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">Baixar todos os meus dados</h3>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Gera um arquivo com tudo o que está na sua conta (transações, categorias, metas e mais). É o seu direito de levar seus dados com você.
            </p>
        </div>
        <button type="button" @click="downloadFull()" :disabled="downloadingFull" class="btn-secondary w-full sm:w-auto">
            <span x-show="!downloadingFull">Baixar todos os meus dados</span>
            <span x-show="downloadingFull" x-cloak>Preparando…</span>
        </button>
    </div>
</section>
