@php
	$enabled ??= false;
	$nameFieldName ??= '';
	$validFromFieldName ??= '';
	$encryptedValidFrom ??= '';
@endphp
@if ($enabled)
	<div class="form-group mb-3 required" style="display: none" aria-hidden="true">
		<input id="{{ $nameFieldName }}"
		       name="{{ $nameFieldName }}"
		       type="text"
		       value=""
		       autocomplete="nope"
		       tabindex="-1"
		>
		<input name="{{ $validFromFieldName }}"
		       type="text"
		       value="{{ $encryptedValidFrom }}"
		       autocomplete="off"
		       tabindex="-1"
		>
	</div>
@endif
