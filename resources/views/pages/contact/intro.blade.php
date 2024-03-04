@if (config('services.googlemaps.key'))
	@php
		$mapHeight = 400;
		$mapPlace = (!empty($city))
			? data_get($city, 'name') . ', ' . config('country.name')
			: config('country.name');
		$mapUrl = getGoogleMapsEmbedUrl(config('services.googlemaps.key'), $mapPlace);
	@endphp
	<div class="intro-inner" style="height: {{ $mapHeight }}px;">
		<iframe
				id="googleMaps"
				width="100%"
				height="{{ $mapHeight }}"
				style="border:0;"
				loading="lazy"
				title="{{ $mapPlace }}"
				aria-label="{{ $mapPlace }}"
				src="{{ $mapUrl }}"
		></iframe>
	</div>
@endif

@section('after_scripts')
	@parent
	@if (config('services.googlemaps.key'))
		<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.googlemaps.key') }}" type="text/javascript"></script>
	@endif
@endsection
