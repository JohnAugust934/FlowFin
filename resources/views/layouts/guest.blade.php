<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FlowFin') }}</title>

        <!-- Scripts e estilos (Inter self-hosted via app.css) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-neutral-800 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8 bg-neutral-50">
            <div class="mb-2">
                <a href="/" class="flex items-center">
                    <x-brand-logo class="h-12 w-auto" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-4 px-6 py-6 bg-white shadow-card overflow-hidden rounded-2xl border border-neutral-100">
                {{ $slot }}
            </div>
        </div>

        <x-toast />
        @stack('scripts')
    </body>
</html>
