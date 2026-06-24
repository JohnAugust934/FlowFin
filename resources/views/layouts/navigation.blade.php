{{-- Barra de navegação superior — visível no desktop. No mobile usa-se a barra inferior. --}}
<nav x-data="{ open: false }" class="hidden sm:block bg-white/70 dark:bg-neutral-900/60 backdrop-blur-xl border-b border-white/40 dark:border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <x-brand-icon class="h-9 w-9" />
                    </a>
                </div>

                <!-- Links de navegação -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Início
                    </x-nav-link>
                    <x-nav-link :href="route('transactions.history')" :active="request()->routeIs('transactions.history')">
                        Transações
                    </x-nav-link>
                    <x-nav-link :href="route('insights.consciencia')" :active="request()->routeIs('insights.consciencia')">
                        Consciência
                    </x-nav-link>
                    <x-nav-link :href="route('savings.economia')" :active="request()->routeIs('savings.economia')">
                        Economia
                    </x-nav-link>
                    <x-nav-link :href="route('categories.manage')" :active="request()->routeIs('categories.manage')">
                        Categorias
                    </x-nav-link>
                </div>
            </div>

            <!-- Menu do usuário -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <div class="me-4">
                        <x-theme-toggle />
                    </div>

                    <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-quick-add'))"
                        class="btn-primary me-4">
                        + Nova transação
                    </button>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-neutral-500 dark:text-neutral-300 bg-transparent hover:text-neutral-700 dark:hover:text-neutral-100 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                Perfil
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    Sair
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="text-sm font-medium text-neutral-500 hover:text-neutral-700 me-4">Entrar</a>
                    <a href="{{ route('register') }}" class="btn-primary">Criar conta</a>
                @endguest
            </div>
        </div>
    </div>
</nav>
