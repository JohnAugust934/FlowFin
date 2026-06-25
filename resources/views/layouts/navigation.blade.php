{{-- Barra de navegação superior — visível no desktop. No mobile usa-se o cabeçalho compacto + barra inferior. --}}
@php
    // Navegação primária (pilares). Categorias (gestão/config) vai para o menu do usuário,
    // mantendo a barra enxuta e com hierarquia clara.
    $navItems = [
        ['route' => 'dashboard',            'pattern' => 'dashboard',            'label' => 'Início'],
        ['route' => 'transactions.history', 'pattern' => 'transactions.history', 'label' => 'Transações'],
        ['route' => 'insights.consciencia', 'pattern' => 'insights.consciencia', 'label' => 'Consciência'],
        ['route' => 'savings.economia',     'pattern' => 'savings.economia',     'label' => 'Economia'],
        ['route' => 'mindset.mentalidade',  'pattern' => 'mindset.mentalidade',  'label' => 'Mentalidade'],
        ['route' => 'goals.direcionamento', 'pattern' => 'goals.direcionamento', 'label' => 'Direcionamento'],
    ];
    $navActive = 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200';
    $navIdle = 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100/80 dark:text-neutral-300 dark:hover:text-white dark:hover:bg-white/5';
@endphp

<nav class="hidden sm:block sticky top-0 z-30 bg-white/90 dark:bg-neutral-900/90 backdrop-blur-xl border-b border-neutral-200/70 dark:border-white/10 shadow-md shadow-neutral-900/5 dark:shadow-glass-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-4">
            {{-- Marca --}}
            <a href="{{ route('dashboard') }}" class="shrink-0 flex items-center gap-2" aria-label="FlowFin — início">
                <x-brand-icon class="h-9 w-9" />
                <span class="hidden lg:inline text-lg font-semibold tracking-tight text-neutral-900 dark:text-white">FlowFin</span>
            </a>

            {{-- Navegação primária (pills) --}}
            @auth
                <div class="flex-1 flex items-center justify-center min-w-0">
                    <div class="flex items-center gap-1 overflow-x-auto no-scrollbar">
                        @foreach ($navItems as $item)
                            @php $isActive = request()->routeIs($item['pattern']); @endphp
                            <a href="{{ route($item['route']) }}"
                               @if ($isActive) aria-current="page" @endif
                               class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 {{ $isActive ? $navActive : $navIdle }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endauth

            {{-- Cluster de ações à direita --}}
            <div class="shrink-0 flex items-center gap-2">
                @auth
                    <x-theme-toggle />

                    <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-quick-add'))"
                        class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                        </svg>
                        <span class="hidden md:inline">Nova transação</span>
                    </button>

                    <x-dropdown align="right" width="w-56" contentClasses="py-1 bg-white/95 dark:bg-neutral-800/98 backdrop-blur-xl border border-neutral-200/70 dark:border-white/10 shadow-lg">
                        <x-slot name="trigger">
                            <button type="button"
                                    class="flex items-center gap-2 rounded-full p-0.5 pr-2 text-sm font-medium text-neutral-600 dark:text-neutral-200 hover:bg-neutral-100/80 dark:hover:bg-white/5 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50"
                                    aria-label="Menu da conta">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-brand-50 dark:bg-brand-500/20 text-brand-700 dark:text-brand-200 text-sm font-semibold">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span class="hidden lg:inline max-w-[10rem] truncate">{{ Auth::user()->name }}</span>
                                <svg class="hidden lg:block w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-2.5 border-b border-neutral-100 dark:border-white/10">
                                <p class="text-sm font-semibold text-neutral-900 dark:text-white truncate">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">{{ Auth::user()->email }}</p>
                            </div>
                            <x-dropdown-link :href="route('categories.manage')" class="dark:text-neutral-200 dark:hover:bg-neutral-700/70 {{ request()->routeIs('categories.manage') ? 'text-brand-700 dark:!text-brand-200 font-semibold' : '' }}">
                                Categorias
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('profile.edit')" class="dark:text-neutral-200 dark:hover:bg-neutral-700/70">
                                Perfil
                            </x-dropdown-link>
                            <div class="my-1 border-t border-neutral-100 dark:border-white/10"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="text-danger dark:text-danger dark:hover:bg-neutral-700/70">
                                    Sair
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth

                @guest
                    <x-theme-toggle />
                    <a href="{{ route('login') }}" class="text-sm font-medium text-neutral-500 hover:text-neutral-700 dark:text-neutral-300">Entrar</a>
                    <a href="{{ route('register') }}" class="btn-primary">Criar conta</a>
                @endguest
            </div>
        </div>
    </div>
</nav>
