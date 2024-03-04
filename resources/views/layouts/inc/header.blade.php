@php
	$countries ??= collect();
	
	// Search parameters
	$queryString = (request()->getQueryString() ? ('?' . request()->getQueryString()) : '');
	
	$showCountryFlagNextLogo = (config('settings.localization.show_country_flag') == 'in_next_logo');
	
	// Check if the Multi-Countries selection is enabled
	$multiCountryIsEnabled = false;
	$multiCountryLabel = '';
	if ($showCountryFlagNextLogo) {
		if (!empty(config('country.code'))) {
			if ($countries->count() > 1) {
				$multiCountryIsEnabled = true;
				$multiCountryLabel = 'title="' . t('select_country') . '"';
			}
		}
	}
	
	// Country
	$countryName = config('country.name');
	$countryFlag24Url = config('country.flag24_url');
	$countryFlag32Url = config('country.flag32_url');
	
	// Logo Label
	$logoLabel = '';
	if ($multiCountryIsEnabled) {
		$logoLabel = config('settings.app.name') . (!empty($countryName) ? ' ' . $countryName : '');
	}
	
	// User Menu
	$userMenu ??= collect();
@endphp
<div class="header">
	<nav class="navbar fixed-top navbar-site navbar-light bg-light navbar-expand-md" role="navigation">
		<div class="container">
			
			<div class="navbar-identity p-sm-0">
				{{-- Logo --}}
				<a href="{{ url('/') }}" class="navbar-brand logo logo-title">
					<img src="{{ config('settings.app.logo_url') }}"
						 alt="{{ strtolower(config('settings.app.name')) }}"
						 class="main-logo"
						 data-bs-placement="bottom"
						 data-bs-toggle="tooltip"
						 title="{!! $logoLabel !!}"
					/>
				</a>
				{{-- Toggle Nav (Mobile) --}}
				<button class="navbar-toggler -toggler float-end"
						type="button"
						data-bs-toggle="collapse"
						data-bs-target="#navbarsDefault"
						aria-controls="navbarsDefault"
						aria-expanded="false"
						aria-label="Toggle navigation"
				>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30" focusable="false">
						<title>{{ t('Menu') }}</title>
						<path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-miterlimit="10" d="M4 7h22M4 15h22M4 23h22"></path>
					</svg>
				</button>
				{{-- Country Flag (Mobile) --}}
				@if ($showCountryFlagNextLogo)
					@if ($multiCountryIsEnabled)
						@if (!empty($countryFlag24Url))
							<button class="flag-menu country-flag d-md-none d-sm-block d-none btn btn-default float-end"
							        href="#selectCountry"
							        data-bs-toggle="modal"
							>
								<img src="{{ $countryFlag24Url }}" alt="{{ $countryName }}" style="float: left;">
								<span class="caret d-none"></span>
							</button>
						@endif
					@endif
				@endif
			</div>
			
			<div class="navbar-collapse collapse" id="navbarsDefault">
				<ul class="nav navbar-nav me-md-auto navbar-left">
					{{-- Country Flag --}}
					@if ($showCountryFlagNextLogo)
						@if (!empty($countryFlag32Url))
							<li class="flag-menu country-flag d-md-block d-sm-none d-none nav-item"
							    data-bs-toggle="tooltip"
							    data-bs-placement="{{ (config('lang.direction') == 'rtl') ? 'bottom' : 'right' }}" {!! $multiCountryLabel !!}
							>
								@if ($multiCountryIsEnabled)
									<a class="nav-link p-0" data-bs-toggle="modal" data-bs-target="#selectCountry">
										<img class="flag-icon mt-1" src="{{ $countryFlag32Url }}" alt="{{ $countryName }}">
										<span class="caret d-lg-block d-md-none d-sm-none d-none float-end mt-3 mx-1"></span>
									</a>
								@else
									<a class="p-0" style="cursor: default;">
										<img class="flag-icon" src="{{ $countryFlag32Url }}" alt="{{ $countryName }}">
									</a>
								@endif
							</li>
						@endif
					@endif
				</ul>
				
				<ul class="nav navbar-nav ms-auto navbar-right">
					@if (config('settings.list.display_browse_listings_link'))
						<li class="nav-item d-lg-block d-md-none d-sm-block d-block">
							@php
								$currDisplay = config('settings.list.display_mode');
								$browseListingsIconClass = 'fas fa-th-large';
								if ($currDisplay == 'make-list') {
									$browseListingsIconClass = 'fas fa-th-list';
								}
								if ($currDisplay == 'make-compact') {
									$browseListingsIconClass = 'fas fa-bars';
								}
							@endphp
							<a href="{{ \App\Helpers\UrlGen::searchWithoutQuery() }}" class="nav-link">
								<i class="{{ $browseListingsIconClass }}"></i> {{ t('Browse Listings') }}
							</a>
						</li>
					@endif
					@if (!auth()->check())
						<li class="nav-item dropdown no-arrow open-on-hover d-md-block d-sm-none d-none">
							<a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
								<i class="fas fa-user"></i>
								<span>{{ t('log_in') }}</span>
								<i class="bi bi-chevron-down"></i>
							</a>
							<ul id="authDropdownMenu" class="dropdown-menu user-menu shadow-sm">
								<li class="dropdown-item">
									@if (config('settings.security.login_open_in_modal'))
										<a href="#quickLogin" class="nav-link" data-bs-toggle="modal"><i class="fas fa-user"></i> {{ t('log_in') }}</a>
									@else
										<a href="{{ \App\Helpers\UrlGen::login() }}" class="nav-link"><i class="fas fa-user"></i> {{ t('log_in') }}</a>
									@endif
								</li>
								<li class="dropdown-item">
									<a href="{{ \App\Helpers\UrlGen::register() }}" class="nav-link"><i class="far fa-user"></i> {{ t('sign_up') }}</a>
								</li>
							</ul>
						</li>
						<li class="nav-item d-md-none d-sm-block d-block">
							@if (config('settings.security.login_open_in_modal'))
								<a href="#quickLogin" class="nav-link" data-bs-toggle="modal"><i class="fas fa-user"></i> {{ t('log_in') }}</a>
							@else
								<a href="{{ \App\Helpers\UrlGen::login() }}" class="nav-link"><i class="fas fa-user"></i> {{ t('log_in') }}</a>
							@endif
						</li>
						<li class="nav-item d-md-none d-sm-block d-block">
							<a href="{{ \App\Helpers\UrlGen::register() }}" class="nav-link"><i class="far fa-user"></i> {{ t('sign_up') }}</a>
						</li>
					@else
						<li class="nav-item dropdown no-arrow open-on-hover">
							<a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
								<i class="fas fa-user-circle"></i>
								<span>{{ auth()->user()->name }}</span>
								<span class="badge badge-pill badge-important count-threads-with-new-messages d-lg-inline-block d-md-none">0</span>
								<i class="bi bi-chevron-down"></i>
							</a>
							<ul id="userMenuDropdown" class="dropdown-menu user-menu shadow-sm">
								@if ($userMenu->count() > 0)
									@php
										$menuGroup = '';
										$dividerNeeded = false;
									@endphp
									@foreach($userMenu as $key => $value)
										@continue(!$value['inDropdown'])
										@php
											if ($menuGroup != $value['group']) {
												$menuGroup = $value['group'];
												if (!empty($menuGroup) && !$loop->first) {
													$dividerNeeded = true;
												}
											} else {
												$dividerNeeded = false;
											}
										@endphp
										@if ($dividerNeeded)
											<li class="dropdown-divider"></li>
										@endif
										<li class="dropdown-item{{ (isset($value['isActive']) && $value['isActive']) ? ' active' : '' }}">
											<a href="{{ $value['url'] }}">
												<i class="{{ $value['icon'] }}"></i> {{ $value['name'] }}
												@if (!empty($value['countVar']) && !empty($value['countCustomClass']))
													<span class="badge badge-pill badge-important{{ $value['countCustomClass'] }}">0</span>
												@endif
											</a>
										</li>
									@endforeach
								@endif
							</ul>
						</li>
					@endif
					
					@if (config('plugins.currencyexchange.installed'))
						@include('currencyexchange::select-currency')
					@endif
					
					@if (config('settings.single.pricing_page_enabled') == '2')
						<li class="nav-item pricing">
							<a href="{{ \App\Helpers\UrlGen::pricing() }}" class="nav-link">
								<i class="fas fa-tags"></i> {{ t('pricing_label') }}
							</a>
						</li>
					@endif
					
					@php
						$addListingUrl = \App\Helpers\UrlGen::addPost();
						$addListingAttr = '';
						if (!auth()->check()) {
							if (config('settings.single.guest_can_submit_listings') != '1') {
								$addListingUrl = '#quickLogin';
								$addListingAttr = ' data-bs-toggle="modal"';
							}
						}
						if (config('settings.single.pricing_page_enabled') == '1') {
							$addListingUrl = \App\Helpers\UrlGen::pricing();
							$addListingAttr = '';
						}
					@endphp
					<li class="nav-item postadd">
						<a class="btn btn-block btn-border btn-listing" href="{{ $addListingUrl }}"{!! $addListingAttr !!}>
							<i class="far fa-edit"></i> {{ t('Create Listing') }}
						</a>
					</li>
					
					@includeFirst([
						config('larapen.core.customizedViewPath') . 'layouts.inc.menu.select-language',
						'layouts.inc.menu.select-language'
					])
				
				</ul>
			</div>
		
		
		</div>
	</nav>
</div>
