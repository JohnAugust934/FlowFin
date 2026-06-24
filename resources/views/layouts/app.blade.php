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
            <header class="sm:hidden sticky top-0 z-30 bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border-b border-white/40 dark:border-white/10">
                <div class="flex items-center justify-between h-14 px-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <x-brand-icon class="h-8 w-8" />
                    </a>
                    <div class="flex items-center gap-2">
                        <x-theme-toggle />
                        @auth
                            <a href="{{ route('profile.edit') }}" class="flex items-center justify-center w-9 h-9 rounded-full bg-brand-50 dark:bg-brand-500/20 text-brand-700 dark:text-brand-200 text-sm font-semibold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </a>
                        @endauth
                    </div>
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
