@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])
@php($inputId = $attributes->get('id', $name))
<div>
    <label class="form-label" for="{{ $inputId }}">{{ $label }} @if($required)<span class="text-danger" aria-hidden="true">*</span>@endif</label>
    <input
        {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($name)])->merge(['id' => $inputId, 'name' => $name, 'type' => $type, 'value' => old($name, $value)]) }}
        @if($required) required aria-required="true" @endif
        @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @elseif($help) aria-describedby="{{ $inputId }}-help" @endif
    >
    @error($name)<div id="{{ $inputId }}-error" class="invalid-feedback">{{ $message }}</div>@enderror
    @if($help && !$errors->has($name))<div id="{{ $inputId }}-help" class="form-text">{{ $help }}</div>@endif
</div>
