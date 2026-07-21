@props(['name', 'label', 'options' => [], 'selected' => null, 'placeholder' => null, 'required' => false])
@php($inputId = $attributes->get('id', $name))
<div>
    <label class="form-label" for="{{ $inputId }}">{{ $label }} @if($required)<span class="text-danger" aria-hidden="true">*</span>@endif</label>
    <select {{ $attributes->except('id')->class(['form-select', 'is-invalid' => $errors->has($name)])->merge(['id' => $inputId, 'name' => $name]) }} @if($required) required aria-required="true" @endif @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif>
        @if($placeholder)<option value="">{{ $placeholder }}</option>@endif
        @foreach($options as $value => $optionLabel)<option value="{{ $value }}" @selected((string) old($name, $selected) === (string) $value)>{{ $optionLabel }}</option>@endforeach
    </select>
    @error($name)<div id="{{ $inputId }}-error" class="invalid-feedback">{{ $message }}</div>@enderror
</div>
