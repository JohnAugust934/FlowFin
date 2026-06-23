<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FlowFin') }}</title>

        <!-- Scripts e estilos (a fonte Inter é self-hosted via @fontsource, incluída no app.css) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-neutral-800">
        <div class="min-h-screen bg-neutral-50">
            <!-- Navegação superior (desktop) -->
            @include('layouts.navigation')

            <!-- Cabeçalho compacto (mobile) -->
            <header class="sm:hidden sticky top-0 z-30 bg-white border-b border-neutral-100">
                <div class="flex items-center justify-between h-14 px-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <x-brand-logo class="h-7 w-auto" />
                    </a>
                    @auth
                        <a href="{{ route('profile.edit') }}" class="flex items-center justify-center w-9 h-9 rounded-full bg-brand-50 text-brand-700 text-sm font-semibold">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </a>
                    @endauth
                </div>
            </header>

            <!-- Título da página (opcional) -->
            @isset($header)
                <header class="bg-white border-b border-neutral-100">
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
