@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'bag' => 'default',
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->getBag($bag)->first($name);
    $selected = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
    $selectAttributes = $attributes->except('id')->class(['form-select', 'is-invalid' => $error]);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label fw-medium">
            <span @class(['required' => $required])>{{ $label }}</span>
        </label>
    @endif

    <select id="{{ $id }}" name="{{ $name }}" @required($required) {{ $selectAttributes }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @endif

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
