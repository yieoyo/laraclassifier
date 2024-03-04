@php
	$admin ??= null;
	$city ??= null;
	$cat ??= null;
	
	$cats ??= [];
	
	// Keywords
	$keywords = request()->query('q');
	$keywords = (is_string($keywords)) ? $keywords : null;
	$keywords = rawurldecode($keywords);
	
	// Category
	$qCategory = request()->query('c');
	$qCategory = (is_numeric($qCategory) || is_string($qCategory)) ? $qCategory : null;
	$qCategory = data_get($cat, 'id', $qCategory);
	
	// Location
	$qLocationId = 0;
	$qAdminName = null;
	if (!empty($city)) {
		$qLocationId = data_get($city, 'id') ?? 0;
		$qLocation = data_get($city, 'name');
	} else {
		$qLocationId = request()->query('l');
		$qLocation = request()->query('location');
		$qAdminName = request()->query('r');
		
		$qLocationId = (is_numeric($qLocationId)) ? $qLocationId : null;
		$qLocation = (is_string($qLocation)) ? $qLocation : null;
		$qAdminName = (is_string($qAdminName)) ? $qAdminName : null;
		
		if (!empty($qAdminName)) {
			$qAdminName = data_get($admin, 'name', $qAdminName);
			$isAdminCode = (bool)preg_match('#^[a-z]{2}\.(.+)$#i', $qAdminName);
			$qLocation = !$isAdminCode ? t('area') . rawurldecode($qAdminName) : null;
		}
	}
	
	// FilterBy
	$qFilterBy = request()->query('filterBy');
	$qFilterBy = (is_string($qFilterBy)) ? $qFilterBy : null;
	
	$displayStatesSearchTip = config('settings.list.display_states_search_tip');
@endphp
@includeFirst([config('larapen.core.customizedViewPath') . 'home.inc.spacer', 'home.inc.spacer'])
<div class="container mb-2 serp-search-bar">
	<form id="search" name="search" action="{{ \App\Helpers\UrlGen::searchWithoutQuery() }}" method="GET">
		@if (!empty($qFilterBy))
			<input type="hidden" name="filterBy" value="{{ $qFilterBy }}">
		@endif
		<div class="row m-0">
			<div class="col-12 px-1 py-sm-1 bg-primary rounded">
				<div class="row gx-1 gy-1">
			
					<div class="col-xl-3 col-md-3 col-sm-12 col-12">
						<select name="c" id="catSearch" class="form-control selecter">
							<option value="" {{ ($qCategory=='') ? 'selected="selected"' : '' }}>
								{{ t('all_categories') }}
							</option>
							@if (!empty($cats))
								@foreach ($cats as $itemCat)
									<option value="{{ data_get($itemCat, 'id') }}" @selected($qCategory == data_get($itemCat, 'id'))>
										{{ data_get($itemCat, 'name') }}
									</option>
								@endforeach
							@endif
						</select>
					</div>
					
					<div class="col-xl-4 col-md-4 col-sm-12 col-12">
						<input name="q" class="form-control keyword" type="text" placeholder="{{ t('what') }}" value="{{ $keywords }}">
					</div>
					
					<input type="hidden" id="rSearch" name="r" value="{{ $qAdminName }}">
					<input type="hidden" id="lSearch" name="l" value="{{ $qLocationId }}">
					
					<div class="col-xl-3 col-md-3 col-sm-12 col-12 search-col locationicon">
						@if ($displayStatesSearchTip)
							<input class="form-control locinput input-rel searchtag-input"
								   type="text"
								   id="locSearch"
								   name="location"
								   placeholder="{{ t('where') }}"
								   value="{{ $qLocation }}"
								   data-bs-placement="top"
								   data-bs-toggle="tooltipHover"
								   title="{{ t('Enter a city name OR a state name with the prefix', ['prefix' => t('area')]) . t('State Name') }}"
							>
						@else
							<input class="form-control locinput input-rel searchtag-input"
								   type="text"
								   id="locSearch"
								   name="location"
								   placeholder="{{ t('where') }}"
								   value="{{ $qLocation }}"
							>
						@endif
					</div>
					
					<div class="col-xl-2 col-md-2 col-sm-12 col-12">
						<button class="btn btn-block btn-primary">
							<i class="fa fa-search"></i> <strong>{{ t('find') }}</strong>
						</button>
					</div>
		
				</div>
			</div>
		</div>
	</form>
</div>

@section('after_scripts')
	@parent
	<script>
		$(document).ready(function () {
			$('#locSearch').on('change', function () {
				if ($(this).val() == '') {
					$('#lSearch').val('');
					$('#rSearch').val('');
				}
			});
		});
	</script>
@endsection
