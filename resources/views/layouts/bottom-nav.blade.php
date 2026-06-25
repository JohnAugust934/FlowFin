{{-- Navegação inferior — padrão mobile. Oculta no desktop (sm:hidden). --}}
@php
    $isDashboard = request()->routeIs('dashboard');
    $isHistory = request()->routeIs('transactions.history');
    $isConsciencia = request()->routeIs('insights.consciencia');
    $isEconomia = request()->routeIs('savings.economia');
    $itemBase = 'flex flex-col items-center justify-center gap-0.5 flex-1 py-2 text-xs font-medium transition';
    $active = 'text-brand-600 dark:text-brand-300';
    $idle = 'text-neutral-400 hover:text-neutral-600 dark:text-neutral-500 dark:hover:text-neutral-300';
@endphp

<nav class="sm:hidden fixed bottom-0 inset-x-0 z-40 bg-white/75 dark:bg-neutral-900/70 backdrop-blur-xl border-t border-white/40 dark:border-white/10 pb-[calc(env(safe-area-inset-bottom)+0.75rem)]"
     aria-label="Navegação principal">
    <div class="flex items-stretch justify-around max-w-md mx-auto px-2 relative">
        {{-- Início --}}
        <a href="{{ route('dashboard') }}" class="{{ $itemBase }} {{ $isDashboard ? $active : $idle }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10" />
            </svg>
            Início
        </a>

        {{-- Transações --}}
        <a href="{{ route('transactions.history') }}" class="{{ $itemBase }} {{ $isHistory ? $active : $idle }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h13M7 7l3-3M7 7l3 3M17 17H4m13 0l-3-3m3 3l-3 3" />
            </svg>
            Transações
        </a>

        {{-- Botão central de ação (abre o registro rápido de transação) --}}
        <div class="flex-1 flex justify-center">
            <button type="button" aria-label="Nova transação"
               onclick="window.dispatchEvent(new CustomEvent('open-quick-add'))"
               class="-mt-5 flex items-center justify-center w-14 h-14 rounded-full bg-gradient-brand text-white shadow-lg shadow-brand-600/30 hover:opacity-95 transition">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
            </button>
        </div>

        {{-- Consciência --}}
        <a href="{{ route('insights.consciencia') }}" class="{{ $itemBase }} {{ $isConsciencia ? $active : $idle }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5L9 7.5l4 4 5-6m0 0h-3.5m3.5 0V9M3.75 20.25h16.5" />
            </svg>
            Consciência
        </a>

        {{-- Economia --}}
        <a href="{{ route('savings.economia') }}" class="{{ $itemBase }} {{ $isEconomia ? $active : $idle }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9v3" />
            </svg>
            Economia
        </a>
    </div>
</nav>
