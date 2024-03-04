@php
	$getSearchFormOp = $getSearchFormOp ?? [];
	$getLocationsOp = $getLocationsOp ?? [];
@endphp
<style>
/* === Homepage: Search Form Area === */
@if (isset($getSearchFormOp['height']) && !empty($getSearchFormOp['height']))
	<?php $getSearchFormOp['height'] = strToDigit($getSearchFormOp['height']) . 'px'; ?>
	#homepage .intro:not(.only-search-bar) {
		height: {{ $getSearchFormOp['height'] }};
		max-height: {{ $getSearchFormOp['height'] }};
	}
@endif
@if (isset($getSearchFormOp['background_color']) && !empty($getSearchFormOp['background_color']))
	#homepage .intro:not(.only-search-bar) {
		background: {{ $getSearchFormOp['background_color'] }};
	}
@endif
@php
	$bgImgFound = false;
	$bgImgDarken = data_get($getSearchFormOp, 'background_image_darken', 0.0);
@endphp
@if (!empty(config('country.background_image_url')))
	#homepage .intro:not(.only-search-bar) {
		background-image: linear-gradient(rgba(0, 0, 0, {{ $bgImgDarken }}),rgba(0, 0, 0, {{ $bgImgDarken }})),url({{ config('country.background_image_url') }});
		background-size: cover;
	}
	@php
		$bgImgFound = true;
	@endphp
@endif
@if (!$bgImgFound)
	@if (isset($getSearchFormOp['background_image_url']) && !empty($getSearchFormOp['background_image_url']))
		#homepage .intro:not(.only-search-bar) {
			background-image: linear-gradient(rgba(0, 0, 0, {{ $bgImgDarken }}),rgba(0, 0, 0, {{ $bgImgDarken }})),url({{ $getSearchFormOp['background_image_url'] }});
			background-size: cover;
		}
	@endif
@endif
@if (isset($getSearchFormOp['big_title_color']) && !empty($getSearchFormOp['big_title_color']))
	#homepage .intro:not(.only-search-bar) h1 {
		color: {{ $getSearchFormOp['big_title_color'] }};
	}
@endif
@if (isset($getSearchFormOp['sub_title_color']) && !empty($getSearchFormOp['sub_title_color']))
	#homepage .intro:not(.only-search-bar) p {
		color: {{ $getSearchFormOp['sub_title_color'] }};
	}
@endif
@if (isset($getSearchFormOp['form_border_width']) && !empty($getSearchFormOp['form_border_width']))
	<?php $getSearchFormOp['form_border_width'] = strToDigit($getSearchFormOp['form_border_width']) . 'px'; ?>
	#homepage .search-row .search-col:first-child .search-col-inner,
	#homepage .search-row .search-col .search-col-inner,
	#homepage .search-row .search-col .search-btn-border {
		border-width: {{ $getSearchFormOp['form_border_width'] }};
	}
	
	@media (max-width: 767px) {
		.search-row .search-col:first-child .search-col-inner,
		.search-row .search-col .search-col-inner,
		.search-row .search-col .search-btn-border {
			border-width: {{ $getSearchFormOp['form_border_width'] }};
		}
	}
@endif
<?php
if (isset($getSearchFormOp['form_border_radius']) && !empty($getSearchFormOp['form_border_radius'])) {
	$formBorderRadius = strToDigit($getSearchFormOp['form_border_radius']);
	
	// Based on default radius
	$fieldsBorderRadius = (int)round((($formBorderRadius * 18) / 24));
	
	// Based on the default radius & default border width
	if (isset($getSearchFormOp['form_border_width']) && !empty($getSearchFormOp['form_border_width'])) {
		$formBorderWidth = strToDigit($getSearchFormOp['form_border_width']);
		
		// Get the difference between the default wrapper & the fields radius, based on the default border width
		$borderRadiusDiff = (24 - 18) / 5;
		
		// Apply the diff. obtained above to the customized wrapper radius to get the fields radius
		$fieldsBorderRadius = (int)round(($formBorderRadius - $borderRadiusDiff));
	}
} else {
	$formBorderRadius = 24;
	$fieldsBorderRadius = 24;
}

$formBorderRadiusOut = getFormBorderRadiusCSS($formBorderRadius, $fieldsBorderRadius);
?>

{!! $formBorderRadiusOut !!}

@if (isset($getSearchFormOp['form_border_color']) && !empty($getSearchFormOp['form_border_color']))
	#homepage .search-row .search-col:first-child .search-col-inner,
	#homepage .search-row .search-col .search-col-inner,
	#homepage .search-row .search-col .search-btn-border {
		border-color: {{ $getSearchFormOp['form_border_color'] }};
	}
	
	@media (max-width: 767px) {
		#homepage .search-row .search-col:first-child .search-col-inner,
		#homepage .search-row .search-col .search-col-inner,
		#homepage .search-row .search-col .search-btn-border {
			border-color: {{ $getSearchFormOp['form_border_color'] }};
		}
	}
@endif
@if (isset($getSearchFormOp['form_btn_background_color']) && !empty($getSearchFormOp['form_btn_background_color']))
	.skin #homepage button.btn-search {
		background-color: {{ $getSearchFormOp['form_btn_background_color'] }};
		border-color: {{ $getSearchFormOp['form_btn_background_color'] }};
	}
@endif
@if (isset($getSearchFormOp['form_btn_text_color']) && !empty($getSearchFormOp['form_btn_text_color']))
	.skin #homepage button.btn-search {
		color: {{ $getSearchFormOp['form_btn_text_color'] }};
	}
@endif
@if (!empty(config('settings.style.page_width')))
	<?php $pageWidth = strToDigit(config('settings.style.page_width')) . 'px'; ?>
	@media (min-width: 1200px) {
		#homepage .intro.only-search-bar .container {
			max-width: {{ $pageWidth }};
		}
	}
@endif

/* === Homepage: Locations & Country Map === */
@if (isset($getLocationsOp['background_color']) && !empty($getLocationsOp['background_color']))
	#homepage .inner-box {
		background: {{ $getLocationsOp['background_color'] }};
	}
@endif
@if (isset($getLocationsOp['border_width']) && !empty($getLocationsOp['border_width']))
	<?php $getLocationsOp['border_width'] = strToDigit($getLocationsOp['border_width']) . 'px'; ?>
	#homepage .inner-box {
		border-width: {{ $getLocationsOp['border_width'] }};
	}
@endif
@if (isset($getLocationsOp['border_color']) && !empty($getLocationsOp['border_color']))
	#homepage .inner-box {
		border-color: {{ $getLocationsOp['border_color'] }};
	}
@endif
@if (isset($getLocationsOp['text_color']) && !empty($getLocationsOp['text_color']))
	#homepage .inner-box,
	#homepage .inner-box p,
	#homepage .inner-box h1,
	#homepage .inner-box h2,
	#homepage .inner-box h3,
	#homepage .inner-box h4,
	#homepage .inner-box h5 {
		color: {{ $getLocationsOp['text_color'] }};
	}
@endif
@if (isset($getLocationsOp['link_color']) && !empty($getLocationsOp['link_color']))
	#homepage .inner-box a {
		color: {{ $getLocationsOp['link_color'] }};
	}
@endif
@if (isset($getLocationsOp['link_color_hover']) && !empty($getLocationsOp['link_color_hover']))
	#homepage .inner-box a:hover,
	#homepage .inner-box a:focus {
		color: {{ $getLocationsOp['link_color_hover'] }};
	}
@endif
</style>
