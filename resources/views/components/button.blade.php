@props([
    'variant' => 'primary', // primary | secondary | success | danger
    'type' => 'button',
    'loading' => false,
])

@php
    $classes = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'success' => 'btn-success',
        'danger' => 'btn-danger',
    ][$variant] ?? 'btn-primary';
@endphp

{{--
    Botão reutilizável com estados normal/hover/disabled/loading.
    Use `variant` para o estilo e `loading` para o estado de carregamento.
    O spinner também pode ser controlado dinamicamente via Alpine, ligando
    `loading`/`disabled` por x-bind no uso do componente.
--}}
<button
    type="{{ $type }}"
    @if ($loading) disabled @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if ($loading)
        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    @endif

    {{ $slot }}
</button>
