@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'hint' => null,      // texto auxiliar em linguagem humana
    'required' => false,
])

{{--
    Campo de formulário completo: rótulo + input temático + mensagem de erro.
    Atributos extras (ex.: inputmode, autocomplete, x-model) são repassados ao input.
--}}
<div class="space-y-1">
    @if ($label)
        <x-input-label :for="$name" :value="$label" />
    @endif

    <x-text-input
        :id="$name"
        :name="$name"
        :type="$type"
        :value="$value"
        :placeholder="$placeholder"
        :required="$required"
        class="block w-full"
        {{ $attributes }}
    />

    @if ($hint)
        <p class="text-xs text-neutral-500">{{ $hint }}</p>
    @endif

    @if ($name)
        <x-input-error :messages="$errors->get($name)" class="mt-1" />
    @endif
</div>
