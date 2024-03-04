@php
	$autoAdvertising ??= [];
@endphp
@if (!empty($autoAdvertising))
	<div class="row d-flex justify-content-center m-0 p-0">
		<div class="col-12 text-center m-0 p-0">
			{!! data_get($autoAdvertising, 'tracking_code_large') !!}
		</div>
	</div>
@endif
