<div class="mb-3 form-check" style="margin-top: 30px;">
	<input type="hidden" name="{{ $name }}" value="{{ $options[0] }}" />
	<input{{ $value == $options[1] ? " checked" : "" }} {{ isset($disabled) && $disabled == true ? ' disabled="disabled"' : "" }}
		type="checkbox"
		id="{{ $name }}"
		name="{{ $name }}"
		value="{{ $options[1] }}"
		class="form-check-input{{ $varNameError ?? '' }} {{ $classes }}  {{ $class ?? "" }}"
		data-on-text="On"
		data-off-text="Off"
		data-on-color="success"
		data-off-color="default"
	>
	<label class="form-check-label">
		@if (!empty($label))
			{!! $label !!}
		@endif
	</label>
	
	@if (isset($hint) && !empty($hint))
		<div class="form-text">{!! $hint !!}</div>
	@endif
</div>
