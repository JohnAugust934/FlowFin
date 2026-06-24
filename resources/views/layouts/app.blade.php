<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FlowFin') }}</title>

        {{-- Aplica o tema (claro/escuro/sistema) antes da pintura para evitar flash. --}}
        <script>
            (function () {
                try {
                    var t = localStorage.getItem('theme') || 'system';
                    var dark = t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.classList.toggle('dark', dark);
                } catch (e) {}
            })();
        </script>

        <!-- Scripts e estilos (a fonte Inter é self-hosted via @fontsource, incluída no app.css) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="app-canvas">
            <!-- Navegação superior (desktop) -->
            @include('layouts.navigation')

            <!-- Cabeçalho compacto (mobile) -->
            @php
                $inMoreMenu = request()->routeIs('mindset.mentalidade') || request()->routeIs('goals.direcionamento') || request()->routeIs('categories.manage');
                $mMenuActive = 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200';
                $mMenuIdle = 'text-neutral-700 hover:bg-neutral-100/80 dark:text-neutral-200 dark:hover:bg-white/5';
            @endphp
            <header class="sm:hidden sticky top-0 z-30 bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border-b border-white/40 dark:border-white/10">
                <div class="flex items-center justify-between h-14 px-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2" aria-label="FlowFin — início">
                        <x-brand-icon class="h-8 w-8" />
                        <span class="text-base font-semibold tracking-tight text-neutral-900 dark:text-white">FlowFin</span>
                    </a>
                    @auth
                        <div class="flex items-center gap-1.5" x-data="{ open: false }" @keydown.escape.window="open = false">
                            {{-- Avatar / perfil --}}
                            <a href="{{ route('profile.edit') }}"
                               class="flex items-center justify-center w-9 h-9 rounded-full bg-brand-50 dark:bg-brand-500/20 text-brand-700 dark:text-brand-200 text-sm font-semibold"
                               aria-label="Sua conta">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </a>

                            {{-- Botão do menu (telas secundárias + tema + sair) --}}
                            <button type="button" @click="open = ! open" :aria-expanded="open"
                                    class="flex items-center justify-center w-9 h-9 rounded-full transition {{ $inMoreMenu ? 'text-brand-600 dark:text-brand-300 bg-brand-50 dark:bg-brand-500/15' : 'text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100/80 dark:hover:bg-white/5' }}"
                                    aria-label="Menu">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                                </svg>
                            </button>

                            {{-- Overlay --}}
                            <div x-show="open" x-transition.opacity @click="open = false"
                                 class="fixed inset-0 z-40 bg-neutral-900/20 dark:bg-black/40" style="display:none;"></div>

                            {{-- Painel de vidro --}}
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                                 class="glass absolute right-4 top-[3.75rem] z-50 w-64 origin-top-right p-2"
                                 style="display:none;">
                                <p class="px-3 pt-1 pb-2 text-[11px] font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Explorar</p>

                                <a href="{{ route('mindset.mentalidade') }}" @click="open = false"
                                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('mindset.mentalidade') ? $mMenuActive : $mMenuIdle }}">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3a6.5 6.5 0 00-4 11.64V17a2 2 0 002 2h4a2 2 0 002-2v-2.36A6.5 6.5 0 0012 3zM9.5 21h5" /></svg>
                                    Mentalidade
                                </a>
                                <a href="{{ route('goals.direcionamento') }}" @click="open = false"
                                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('goals.direcionamento') ? $mMenuActive : $mMenuIdle }}">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="5" /><circle cx="12" cy="12" r="1.5" /></svg>
                                    Direcionamento
                                </a>
                                <a href="{{ route('categories.manage') }}" @click="open = false"
                                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('categories.manage') ? $mMenuActive : $mMenuIdle }}">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5a2 2 0 00-2 2v4.568a2 2 0 00.586 1.414l9 9a2 2 0 002.828 0l4.568-4.568a2 2 0 000-2.828l-9-9A2 2 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h.01" /></svg>
                                    Categorias
                                </a>

                                <div class="my-2 border-t border-white/50 dark:border-white/10"></div>

                                <div class="flex items-center justify-between gap-2 px-3 py-1.5">
                                    <span class="text-sm font-medium text-neutral-700 dark:text-neutral-200">Tema</span>
                                    <x-theme-toggle />
                                </div>

                                <div class="my-2 border-t border-white/50 dark:border-white/10"></div>

                                <a href="{{ route('profile.edit') }}" @click="open = false"
                                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $mMenuIdle }}">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path stroke-linecap="round" stroke-linejoin="round" d="M4 21a8 8 0 0116 0" /></svg>
                                    Perfil
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-danger hover:bg-danger-light/60 dark:hover:bg-white/5 transition">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m0 0l4-4m-4 4l4 4M13 4h6a1 1 0 011 1v14a1 1 0 01-1 1h-6" /></svg>
                                        Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth
                    @guest
                        <x-theme-toggle />
                    @endguest
                </div>
            </header>

            <!-- Título da página (opcional) -->
            @isset($header)
                <header class="bg-white/60 dark:bg-neutral-900/50 backdrop-blur-md border-b border-white/40 dark:border-white/10">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Conteúdo. Padding inferior no mobile para não cobrir com a barra inferior. -->
            <main class="pb-24 sm:pb-8">
                {{ $slot }}
            </main>

            <!-- Navegação inferior (mobile) -->
            @include('layouts.bottom-nav')
        </div>

        <!-- Formulário global de transação (registro rápido / edição) -->
        @auth
            <x-transaction-form />
        @endauth

        <!-- Container de toasts (feedback visual imediato) -->
        <x-toast />

        @stack('scripts')
    </body>
</html>
