{{-- Alternância de tema: claro / escuro / sistema. Persiste a escolha (localStorage)
     e aplica via classe `dark` no <html>. Lógica no componente Alpine `themeControl`. --}}
@php
    $btn = 'flex items-center justify-center w-7 h-7 rounded-full transition';
    $on = "'bg-white text-brand-600 shadow-sm dark:bg-neutral-700 dark:text-brand-200'";
    $off = "'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200'";
@endphp
<div x-data="themeControl"
     class="inline-flex items-center gap-0.5 p-0.5 rounded-full bg-neutral-200/70 dark:bg-neutral-800/70 border border-white/50 dark:border-white/10"
     role="group" aria-label="Tema da tela">
    {{-- Claro --}}
    <button type="button" @click="set('light')" :aria-pressed="mode === 'light'"
            class="{{ $btn }}" :class="mode === 'light' ? {{ $on }} : {{ $off }}"
            aria-label="Tema claro" title="Claro">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="4" />
            <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41" />
        </svg>
    </button>
    {{-- Escuro --}}
    <button type="button" @click="set('dark')" :aria-pressed="mode === 'dark'"
            class="{{ $btn }}" :class="mode === 'dark' ? {{ $on }} : {{ $off }}"
            aria-label="Tema escuro" title="Escuro">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
        </svg>
    </button>
    {{-- Sistema --}}
    <button type="button" @click="set('system')" :aria-pressed="mode === 'system'"
            class="{{ $btn }}" :class="mode === 'system' ? {{ $on }} : {{ $off }}"
            aria-label="Seguir o tema do sistema" title="Sistema">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="4" width="18" height="12" rx="2" />
            <path stroke-linecap="round" d="M8 20h8m-4-4v4" />
        </svg>
    </button>
</div>
