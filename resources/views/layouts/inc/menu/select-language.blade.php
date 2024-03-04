@php
	$countries ??= collect();
	$showCountryFlagNextLang = (config('settings.localization.show_country_flag') == 'in_next_lang');
	
	$showCountrySpokenLang = config('settings.localization.show_country_spoken_languages');
	$showCountrySpokenLang = str_starts_with($showCountrySpokenLang, 'active');
	$supportedLanguages = $showCountrySpokenLang ? getCountrySpokenLanguages() : getSupportedLanguages();
	
	$supportedLanguagesExist = (is_array($supportedLanguages) && count($supportedLanguages) > 1);
	$isLangOrCountryCanBeSelected = ($supportedLanguagesExist || $showCountryFlagNextLang);
	
	// Check if the Multi-Countries selection is enabled
	$multiCountryIsEnabled = false;
	$multiCountryLabel = '';
	if ($showCountryFlagNextLang) {
		if (!empty(config('country.code'))) {
			if ($countries->count() > 1) {
				$multiCountryIsEnabled = true;
			}
		}
	}
	
	$countryName = config('country.name');
	$countryFlag32Url = config('country.flag32_url');
	
	$countryFlagImg = $showCountryFlagNextLang
		? '<img class="flag-icon" src="' . $countryFlag32Url . '" alt="' . $countryName . '">'
		: null;
@endphp
@if ($isLangOrCountryCanBeSelected)
	{{-- Language & Country Selector --}}
	<li class="nav-item dropdown lang-menu no-arrow open-on-hover">
		<a href="#" class="dropdown-toggle nav-link pt-1" data-bs-toggle="dropdown" id="langDropdown">
			@if (!empty($countryFlagImg))
				<span>
					{!! $countryFlagImg !!}
					{{ strtoupper(config('app.locale')) }}
				</span>
			@else
				<span><i class="bi bi-globe2"></i></span>
				<i class="bi bi-chevron-down"></i>
			@endif
		</a>
		<ul id="langDropdownItems"
			class="dropdown-menu dropdown-menu-end user-menu shadow-sm"
			role="menu"
			aria-labelledby="langDropdown"
		>
			@if ($supportedLanguagesExist)
				<li class="px-3 pt-2 pb-3">
					{{ t('language') }}
				</li>
				@foreach($supportedLanguages as $langCode => $lang)
					@php
						$langFlag = $lang['flag'] ?? '';
						$langFlagCountry = str_replace('flag-icon-', '', $langFlag);
						$isFlagEnabled = (
							config('settings.localization.show_languages_flags')
							&& !empty(trim($langFlag)) && is_string($langFlag)
						);
						$isActive = (strtolower($langCode) == strtolower(config('app.locale')));
					@endphp
					<li class="dropdown-item{{ $isActive ? ' active' : '' }}">
						<a href="{{ url('locale/' . $langCode) }}"
						   tabindex="-1"
						   rel="alternate"
						   hreflang="{{ $lang['tag'] ?? getLangTag($langCode) }}"
						   title="{{ $lang['name'] }}"
						>
							@php
								$checkBox = $isActive
											? '<i class="fas fa-dot-circle"></i>'
											: '<i class="far fa-circle"></i>';
								$checkBox .= '&nbsp;';
								
								// $langFlag = '<i class="flag-icon ' . $langFlag . '"></i>';
								$langFlag = '<img src="' . getCountryFlagUrl($langFlagCountry) . '">&nbsp;';
								$langFlag .= '&nbsp;';
								
								$langPrefix = $isFlagEnabled ? $langFlag : $checkBox;
							@endphp
							{!! $langPrefix . $lang['native'] !!}
						</a>
					</li>
				@endforeach
			@endif
			
			@if ($showCountryFlagNextLang)
				@if ($multiCountryIsEnabled)
					<li class="dropdown-divider mt-2"></li>
					@php
						$surfingOn = t('surfing_on', [
							'appName' => config('app.name'),
							'country' => $countryName
						]);
						$changeCountry = t('change_country');
					@endphp
					<li class="px-3 py-2 text-secondary">
						@if (!empty($countryFlagImg))
							{!! $countryFlagImg !!}
						@endif
						{{ $surfingOn }}
					</li>
					<li class="dropdown-item mb-1">
						<a data-bs-toggle="modal"
						   data-bs-target="#selectCountry"
						   class="btn btn-sm btn-default rounded-pill"
						   title="{{ $changeCountry }}"
						>
							{{ str($changeCountry)->limit(25)->toString() }}
						</a>
					</li>
				@endif
			@endif
		</ul>
	</li>
@endif
