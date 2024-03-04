@php
	$sectionOptions = $getSearchFormOp ?? [];
	$sectionData ??= [];
	
	// Get Search Form Options
	$enableFormAreaCustomization = data_get($sectionOptions, 'enable_extended_form_area') ?? '0';
	$hideTitles = data_get($sectionOptions, 'hide_titles') ?? '0';
	
	$headerTitle = data_get($sectionOptions, 'title_' . config('app.locale'));
	$headerTitle = (!empty($headerTitle)) ? replaceGlobalPatterns($headerTitle) : null;
	
	$headerSubTitle = data_get($sectionOptions, 'sub_title_' . config('app.locale'));
	$headerSubTitle = (!empty($headerSubTitle)) ? replaceGlobalPatterns($headerSubTitle) : null;
	
	$parallax = data_get($sectionOptions, 'parallax') ?? '0';
	$hideForm = data_get($sectionOptions, 'hide_form') ?? '0';
	$displayStatesSearchTip = config('settings.list.display_states_search_tip');
	
	$hideOnMobile = (data_get($sectionOptions, 'hide_on_mobile') == '1') ? ' hidden-sm' : '';
@endphp
@if (isset($enableFormAreaCustomization) && $enableFormAreaCustomization == '1')
	
	@if (isset($firstSection) && !$firstSection)
		<div class="p-0 mt-lg-4 mt-md-3 mt-3"></div>
	@endif
	
	@php
		$parallax = ($parallax == '1') ? ' parallax' : '';
	@endphp
	<div class="intro{{ $hideOnMobile }}{{ $parallax }}">
		<div class="container text-center">
			
			@if ($hideTitles != '1')
				<h1 class="intro-title animated fadeInDown">
					{{ $headerTitle }}
				</h1>
				<p class="sub animateme fittext3 animated fadeIn">
					{!! $headerSubTitle !!}
				</p>
			@endif
			
			@if ($hideForm != '1')
					<form id="search" name="search" action="{{ \App\Helpers\UrlGen::searchWithoutQuery() }}" method="GET">
						<div class="row search-row animated fadeInUp">
							
							<div class="col-md-5 col-sm-12 search-col relative mb-1 mb-xxl-0 mb-xl-0 mb-lg-0 mb-md-0">
								<div class="search-col-inner">
									<i class="fas {{ (config('lang.direction')=='rtl') ? 'fa-angle-double-left' : 'fa-angle-double-right' }} icon-append"></i>
									<div class="search-col-input">
										<input class="form-control has-icon" name="q" placeholder="{{ t('what') }}" type="text" value="">
									</div>
								</div>
							</div>
							
							<input type="hidden" id="lSearch" name="l" value="">
							
							<div class="col-md-5 col-sm-12 search-col relative locationicon mb-1 mb-xxl-0 mb-xl-0 mb-lg-0 mb-md-0">
								<div class="search-col-inner">
									<i class="fas fa-map-marker-alt icon-append"></i>
									<div class="search-col-input">
										@if ($displayStatesSearchTip)
											<input class="form-control locinput input-rel searchtag-input has-icon"
												   id="locSearch"
												   name="location"
												   placeholder="{{ t('where') }}"
												   type="text"
												   value=""
												   data-bs-placement="top"
												   data-bs-toggle="tooltipHover"
												   title="{{ t('Enter a city name OR a state name with the prefix', ['prefix' => t('area')]) . t('State Name') }}"
											>
										@else
											<input class="form-control locinput input-rel searchtag-input has-icon"
												   id="locSearch"
												   name="location"
												   placeholder="{{ t('where') }}"
												   type="text"
												   value=""
											>
										@endif
									</div>
								</div>
							</div>
							
							<div class="col-md-2 col-sm-12 search-col">
								<div class="search-btn-border bg-primary">
									<button class="btn btn-primary btn-search btn-block btn-gradient">
										<i class="fas fa-search"></i> <strong>{{ t('find') }}</strong>
									</button>
								</div>
							</div>
							
						</div>
					</form>
			@endif
			
		</div>
	</div>
	
@else
	
	@includeFirst([config('larapen.core.customizedViewPath') . 'home.inc.spacer', 'home.inc.spacer'])
	<div class="intro only-search-bar{{ $hideOnMobile }}">
		<div class="container text-center">
			
			@if ($hideForm != '1')
				<form id="search" name="search" action="{{ \App\Helpers\UrlGen::searchWithoutQuery() }}" method="GET">
					<div class="row search-row animated fadeInUp">
						
						<div class="col-md-5 col-sm-12 search-col relative mb-1 mb-xxl-0 mb-xl-0 mb-lg-0 mb-md-0">
							<div class="search-col-inner">
								<i class="fas {{ (config('lang.direction')=='rtl') ? 'fa-angle-double-left' : 'fa-angle-double-right' }} icon-append"></i>
								<div class="search-col-input">
									<input class="form-control has-icon" name="q" placeholder="{{ t('what') }}" type="text" value="">
								</div>
							</div>
						</div>
						
						<input type="hidden" id="lSearch" name="l" value="">
						
						<div class="col-md-5 col-sm-12 search-col relative locationicon mb-1 mb-xxl-0 mb-xl-0 mb-lg-0 mb-md-0">
							<div class="search-col-inner">
								<i class="fas fa-map-marker-alt icon-append"></i>
								<div class="search-col-input">
									@if ($displayStatesSearchTip)
										<input class="form-control locinput input-rel searchtag-input has-icon"
											   id="locSearch"
											   name="location"
											   placeholder="{{ t('where') }}"
											   type="text"
											   value=""
											   data-bs-placement="top"
											   data-bs-toggle="tooltipHover"
											   title="{{ t('Enter a city name OR a state name with the prefix', ['prefix' => t('area')]) . t('State Name') }}"
										>
									@else
										<input class="form-control locinput input-rel searchtag-input has-icon"
											   id="locSearch"
											   name="location"
											   placeholder="{{ t('where') }}"
											   type="text"
											   value=""
										>
									@endif
								</div>
							</div>
						</div>
						
						<div class="col-md-2 col-sm-12 search-col">
							<div class="search-btn-border bg-primary">
								<button class="btn btn-primary btn-search btn-block btn-gradient">
									<i class="fas fa-search"></i> <strong>{{ t('find') }}</strong>
								</button>
							</div>
						</div>
					
					</div>
				</form>
			@endif
		
		</div>
	</div>
	
@endif
