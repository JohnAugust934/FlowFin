@props([
    'value' => 0,        // percentual atual (0–100+)
    'label' => null,     // rótulo opcional acima da barra
    'caption' => null,   // texto auxiliar à direita (ex.: "R$ 800 de R$ 1.000")
    'status' => null,    // força a cor; se nulo, é derivada do valor (semafórico)
])

@php
    $pct = max(0, (float) $value);
    // Cor semafórica: ok (<80%), atenção (80–99%), estourado (>=100%).
    $resolved = $status ?? ($pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success'));

    $barColor = [
        'success' => 'bg-gradient-emerald',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
    ][$resolved] ?? 'bg-gradient-emerald';

    $width = min(100, $pct); // a barra nunca passa de 100% visualmente
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($label || $caption)
        <div class="flex items-center justify-between mb-1.5 text-sm">
            @if ($label)
                <span class="font-medium text-neutral-700">{{ $label }}</span>
            @endif
            @if ($caption)
                <span class="text-neutral-500">{{ $caption }}</span>
            @endif
        </div>
    @endif

    <div class="w-full h-2.5 bg-neutral-200 rounded-full overflow-hidden" role="progressbar"
         aria-valuenow="{{ (int) $pct }}" aria-valuemin="0" aria-valuemax="100">
        <div class="h-full {{ $barColor }} rounded-full transition-all duration-500 ease-out"
             style="width: {{ $width }}%"></div>
    </div>
</div>
