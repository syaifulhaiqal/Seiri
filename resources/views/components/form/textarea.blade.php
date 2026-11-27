@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 3,
    'required' => false,
    'help' => null,
    'bag' => 'default',
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->getBag($bag)->first($name);
    $textareaAttributes = $attributes->except('id')->class(['form-control', 'is-invalid' => $error]);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label fw-medium">
            <span @class(['required' => $required])>{{ $label }}</span>
        </label>
    @endif

    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) {{ $textareaAttributes }}>{{ old($name, $value) }}</textarea>
    @if ($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @endif

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
