{{-- Perfil do usuário: dados pessoais, relatórios/exportação (LGPD) e conta. --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-neutral-800 dark:text-neutral-100">Seu perfil</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Seus dados, seus relatórios e sua conta</p>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <x-card>
            @include('profile.partials.update-profile-information-form')
        </x-card>

        <x-card>
            @include('profile.partials.export-data-form')
        </x-card>

        <x-card>
            @include('profile.partials.update-password-form')
        </x-card>

        <x-card class="!border-danger/30">
            @include('profile.partials.delete-user-form')
        </x-card>
    </div>
</x-app-layout>
