{{-- intl tel input --}}
@php
    $phoneError = (isset($errors) && $errors->has('phone')) ? ' is-invalid' : '';
	$phoneValue = $field['value'] ?? ($field['default'] ?? '');
	$phoneCountryValue = $field['phone_country'] ?? 'us';
	$phoneValue = phoneE164($phoneValue, $phoneCountryValue);
	$phoneValueOld = phoneE164(old($field['name'], $phoneValue), old('phone_country', $phoneCountryValue));
@endphp
<div @include('admin.panel.inc.field_wrapper_attributes') >
    <label class="form-label fw-bolder">{!! $field['label'] !!}</label>
    @include('admin.panel.fields.inc.translatable_icon')
    
    @if (isset($field['suffix'])) <div class="input-group"> @endif
    <input
        type="tel"
        name="{{ $field['name'] }}"
        value="{{ $phoneValueOld }}"
        @include('admin.panel.inc.field_attributes')
    >
    @if (isset($field['suffix'])) <span class="input-group-text iti-group-text">{!! $field['suffix'] !!}</span> @endif
    @if (isset($field['suffix'])) </div> @endif
    
    <input name="phone_country" type="hidden" value="{{ old('phone_country', $phoneCountryValue) }}">
    
    {{-- HINT --}}
    @if (isset($field['hint']))
        <div class="form-text">{!! $field['hint'] !!}</div>
    @endif
</div>

@if ($xPanel->checkIfFieldIsFirstOfItsType($field, $fields))
    @push('crud_fields_scripts')
        <script>
            if (typeof phoneCountry === 'undefined') {
                var phoneCountry;
            }
            phoneCountry = '{{ $phoneCountryValue }}';
        </script>
    @endpush
@endif

{{-- Note: you can use @if ($xPanel->checkIfFieldIsFirstOfItsType($field, $fields)) to only load some CSS/JS once, even though there are multiple instances of it --}}