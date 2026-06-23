{{-- Navegação inferior — padrão mobile. Oculta no desktop (sm:hidden). --}}
@php
    $isDashboard = request()->routeIs('dashboard');
    $isProfile = request()->routeIs('profile.*');
    $isHistory = request()->routeIs('transactions.history');
    $isCategories = request()->routeIs('categories.manage');
    $itemBase = 'flex flex-col items-center justify-center gap-0.5 flex-1 py-2 text-xs font-medium transition';
    $active = 'text-brand-600';
    $idle = 'text-neutral-400 hover:text-neutral-600';
@endphp

<nav class="sm:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-neutral-200 pb-[env(safe-area-inset-bottom)]"
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

        {{-- Metas --}}
        <a href="#" class="{{ $itemBase }} {{ $idle }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v3m0 14v3m10-10h-3M5 12H2m15.5-6.5l-2 2m-7 7l-2 2m11 0l-2-2m-7-7l-2-2" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            Metas
        </a>

        {{-- Perfil --}}
        <a href="{{ route('profile.edit') }}" class="{{ $itemBase }} {{ $isProfile ? $active : $idle }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 100-8 4 4 0 000 8zm0 2c-4 0-7 2-7 5v1h14v-1c0-3-3-5-7-5z" />
            </svg>
            Perfil
        </a>
    </div>
</nav>
