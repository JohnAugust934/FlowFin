@props([
    'status' => 'success', // success (ok) | warning (atenção) | danger (estourado) | neutral
])

@php
    $styles = [
        'success' => 'bg-success-light text-success-dark',
        'warning' => 'bg-warning-light text-warning-dark',
        'danger' => 'bg-danger-light text-danger-dark',
        'neutral' => 'bg-neutral-100 text-neutral-600',
    ][$status] ?? 'bg-neutral-100 text-neutral-600';

    $dot = [
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
        'neutral' => 'bg-neutral-400',
    ][$status] ?? 'bg-neutral-400';
@endphp

{{-- Badge semafórico (verde/amarelo/vermelho) para status de metas e orçamentos. --}}
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold $styles"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
    {{ $slot }}
</span>
