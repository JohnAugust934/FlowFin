{{-- Mantido para compatibilidade com as telas do Breeze; usa o estilo primário da marca. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary']) }}>
    {{ $slot }}
</button>
