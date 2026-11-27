@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'help' => null,
    'bag' => 'default',
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->getBag($bag)->first($name);
    $inputValue = $type === 'password' ? null : old($name, $value);
    $inputAttributes = $attributes->except('id')->class(['form-control', 'is-invalid' => $error]);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label fw-medium">
            <span @class(['required' => $required])>{{ $label }}</span>
        </label>
    @endif

    @if ($type === 'password')
        <div class="input-group has-validation" x-data="passwordField">
            <input
                type="password"
                id="{{ $id }}"
                name="{{ $name }}"
                x-ref="input"
                x-bind:type="visible ? 'text' : 'password'"
                @required($required)
                {{ $inputAttributes }}
            >
            <button
                type="button"
                class="btn btn-toggle-password"
                tabindex="-1"
                x-on:click="toggle()"
                x-bind:aria-label="visible ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
            >
                <i class="bi" x-bind:class="visible ? 'bi-eye-slash' : 'bi-eye'"></i>
            </button>
            @if ($error)
                <div class="invalid-feedback">{{ $error }}</div>
            @endif
        </div>
    @else
        <input
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $inputValue }}"
            @required($required)
            {{ $inputAttributes }}
        >
        @if ($error)
            <div class="invalid-feedback">{{ $error }}</div>
        @endif
    @endif

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
