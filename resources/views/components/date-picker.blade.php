@props([
    'id',
    'name' => null,
    'label' => '',
    'value' => '',
    'required' => false,
    'placeholder' => 'DD/MM/AAAA',
])

@php
    $fieldName = $name ?? $id;
    $oldValue = old($fieldName, $value);
@endphp

<div class="mb-2">
    @if($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if($required)<span class="text-danger">*</span>@endif
        </label>
    @endif
    <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
        <input
            type="text"
            id="{{ $id }}"
            name="{{ $fieldName }}"
            class="form-control date-picker"
            value="{{ $oldValue }}"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            {{ $required ? 'required' : '' }}
        >
        <div class="invalid-feedback"></div>
    </div>
</div>
