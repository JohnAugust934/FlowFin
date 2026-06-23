{{-- Mantido para compatibilidade com as telas do Breeze; usa o estilo secundário da marca. --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-secondary']) }}>
    {{ $slot }}
</button>
