@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-neutral-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-neutral-800 placeholder-neutral-400 disabled:bg-neutral-100 disabled:cursor-not-allowed']) }}>
