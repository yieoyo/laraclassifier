<?php
// Clear Filter Button
$clearFilterBtn = \App\Helpers\UrlGen::getDateFilterClearLink($cat ?? null, $city ?? null);
?>
{{-- Date --}}
<div class="block-title has-arrow sidebar-header">
	<h5>
		<span class="fw-bold">
			{{ t('Date Posted') }}
		</span> {!! $clearFilterBtn !!}
	</h5>
</div>
<div class="block-content list-filter">
	<div class="filter-date filter-content">
		<ul>
			@if (isset($periodList) && !empty($periodList))
				@foreach($periodList as $key => $value)
					<li>
						<input type="radio"
							   name="postedDate"
							   value="{{ $key }}"
							   id="postedDate_{{ $key }}" {{ (request()->query('postedDate')==$key) ? 'checked="checked"' : '' }}
						>
						<label for="postedDate_{{ $key }}">{{ $value }}</label>
					</li>
				@endforeach
			@endif
			<input type="hidden" id="postedQueryString" value="{{ \App\Helpers\Arr::query(request()->except(['page', 'postedDate'])) }}">
		</ul>
	</div>
</div>
<div style="clear:both"></div>

@section('after_scripts')
	@parent
	
	<script>
		$(document).ready(function ()
		{
			$('input[type=radio][name=postedDate]').click(function() {
				let postedQueryString = $('#postedQueryString').val();
				
				if (postedQueryString !== '') {
					postedQueryString = postedQueryString + '&';
				}
				postedQueryString = postedQueryString + 'postedDate=' + $(this).val();
				
				let searchUrl = baseUrl + '?' + postedQueryString;
				redirect(searchUrl);
			});
		});
	</script>
@endsection
