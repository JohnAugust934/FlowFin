@props([
    'title' => null,
    'subtitle' => null,
])

{{-- Cartão reutilizável para resumos e seções. --}}
<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || $subtitle || isset($action))
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                @if ($title)
                    <h3 class="text-base font-semibold text-neutral-800">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-sm text-neutral-500 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($action)
                <div class="shrink-0">{{ $action }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</div>
