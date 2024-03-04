<?php
	$currDisplay = config('settings.list.display_mode');
	$typeOfDisplay = [
		'list'    => 'make-list',
		'compact' => 'make-compact',
		'grid'    => 'make-grid',
	];
	// '$display' var is sent from the FileController file through view(...)->render()
	if (isset($display) && !empty($display) && isset($typeOfDisplay[$display])) {
		$currDisplay = $typeOfDisplay[$display];
	}
	
	// Default ribbons positions (related to the page size)
	// https://getbootstrap.com/docs/5.0/layout/breakpoints/

	$rBorderWidth = 12;
	$rWidth = 140;
	$rLeft = 0;
	$rTop = 30;
	$rsFontSize = 11;
	$rsPaddingStart = 10;
	$rsTop = 0;
	
	if (config('settings.list.left_sidebar')) {
		// xxl (>=1400px)
		$rWidthXxl = 140;
		$rsFontSizeXxl = 11;
		
		// xl (>=1200px)
		$rWidthXl = 120;
		$rsFontSizeXl = 11;
		
		// lg (>=992px)
		$rWidthLg = 100;
		$rsFontSizeLg = 10;
		
		// md (>=768px)
		$rWidthMd = 80;
		$rsFontSizeMd = 9;
		$rsTopMd = -1;
		$rsPaddingStartMd = 5;
		
		// sm (>=576px)
		$rWidthSm = 140;
		$rsFontSizeSm = 9;
		$rsTopSm = -1;
		$rsPaddingStartSm = 5;
		
		// xs (<576px)
		$rWidthXs = 180;
		$rsFontSizeXs = 12;
	} else {
		// xxl (>=1400px)
		$rWidthXxl = 160;
		$rsFontSizeXxl = 11;
		
		// xl (>=1200px)
		$rWidthXl = 120;
		$rsFontSizeXl = 11;
		
		// lg (>=992px)
		$rWidthLg = 100;
		$rsFontSizeLg = 10;
		
		// md (>=768px)
		$rWidthMd = 80;
		$rsFontSizeMd = 9;
		$rsTopMd = -1;
		
		// sm (>=576px)
		$rWidthSm = 140;
		$rsFontSizeSm = 9;
		$rsTopSm = -1;
		
		// xs (<576px)
		$rWidthXs = 180;
		$rsFontSizeXs = 11;
	}

	if (in_array($currDisplay, ['make-list', 'make-compact'])) {
		if (config('settings.list.left_sidebar')) {
			$rWidth = $rWidthXxl = 100;
		} else {
			$rWidth = $rWidthXxl = 140;
		}
		$rWidthLg = 90;
		$rWidthMd = 60;
		$rWidthSm = 60;
		$rWidthXs = 250;
		
		$rsFontSizeLg = $rsFontSizeXs = 9;
		$rsTop = -1;
		
		$rsPaddingStart = 5;
		if ($currDisplay == 'make-compact') {
			$rWidthXxl = 130;
			$rWidthXl = 130;
			$rWidthLg = 120;
			$rWidthMd = 100;
			$rWidthSm = 100;
			
			$rBorderWidth = 8;
			
			$rTop = $rTopXs = 0;
			
			$rsFontSize = $rsFontSizeXxl = $rsFontSizeXl = 9;
		}
	} else {
		$gridViewCols = config('settings.list.grid_view_cols');
		
		$rWidthXs = 120;
		$rsFontSizeXs = 10;
		
		if (config('settings.list.left_sidebar')) {
			$rBorderWidth = 10;
			if ($gridViewCols == 4) {
				$rWidth = $rWidthXxl = 120;
				$rsFontSizeXs = 9;
				$rsTop = -1;
			}
			if ($gridViewCols == 3) {
				$rsFontSizeXs = 9;
				$rsTop = -1;
			}
		}
	}
?>
<style>
	
	/* Ribbons: Media Screen - Dynamic */
	@media (min-width: 1400px) {
		.item-list .ribbon-horizontal {
			width: {{ $rWidthXxl ?? $rWidth }}px !important;
			@if (config('lang.direction') == 'rtl')
				right: {{ $rLeftXxl ?? $rLeft }}px !important;
			@else
				left: {{ $rLeftXxl ?? $rLeft }}px !important;
			@endif
			top: {{ $rTopXxl ?? $rTop }}px !important;
			border-width: {{ $rBorderWidthXxl ?? $rBorderWidth }}px;
		}
		.ribbon-horizontal span {
			font-size: {{ $rsFontSizeXxl ?? $rsFontSize }}px;
			@if (config('lang.direction') == 'rtl')
				padding-right: {{ $rsPaddingStartXxl ?? $rsPaddingStart }}px;
			@else
				padding-left: {{ $rsPaddingStartXxl ?? $rsPaddingStart }}px;
			@endif
			top: {{ $rsTopXxl ?? $rsTop }}px !important;
		}
	}
	@media (min-width: 1200px) and (max-width: 1399px) {
		.item-list .ribbon-horizontal {
			width: {{ $rWidthXl ?? $rWidth }}px !important;
			@if (config('lang.direction') == 'rtl')
				right: {{ $rLeftXl ?? $rLeft }}px !important;
			@else
				left: {{ $rLeftXl ?? $rLeft }}px !important;
			@endif
			top: {{ $rTopXl ?? $rTop }}px !important;
			border-width: {{ $rBorderWidthXl ?? $rBorderWidth }}px;
		}
		.ribbon-horizontal span {
			font-size: {{ $rsFontSizeXl ?? $rsFontSize }}px;
			@if (config('lang.direction') == 'rtl')
				padding-right: {{ $rsPaddingStartXl ?? $rsPaddingStart }}px;
			@else
				padding-left: {{ $rsPaddingStartXl ?? $rsPaddingStart }}px;
			@endif
			top: {{ $rsTopXl ?? $rsTop }}px !important;
		}
	}
	@media (min-width: 992px) and (max-width: 1199px) {
		.item-list .ribbon-horizontal {
			width: {{ $rWidthLg ?? $rWidth }}px !important;
			@if (config('lang.direction') == 'rtl')
				right: {{ $rLeftLg ?? $rLeft }}px !important;
			@else
				left: {{ $rLeftLg ?? $rLeft }}px !important;
			@endif
			top: {{ $rTopLg ?? $rTop }}px !important;
			border-width: {{ $rBorderWidthLg ?? $rBorderWidth }}px;
		}
		.ribbon-horizontal span {
			font-size: {{ $rsFontSizeLg ?? $rsFontSize }}px;
			@if (config('lang.direction') == 'rtl')
				padding-right: {{ $rsPaddingStartLg ?? $rsPaddingStart }}px;
			@else
				padding-left: {{ $rsPaddingStartLg ?? $rsPaddingStart }}px;
			@endif
			top: {{ $rsTopLg ?? $rsTop }}px !important;
		}
	}
	@media (min-width: 768px) and (max-width: 991px) {
		.item-list .ribbon-horizontal {
			width: {{ $rWidthMd ?? $rWidth }}px !important;
			@if (config('lang.direction') == 'rtl')
				right: {{ $rLeftMd ?? $rLeft }}px !important;
			@else
				left: {{ $rLeftMd ?? $rLeft }}px !important;
			@endif
			top: {{ $rTopMd ?? $rTop }}px !important;
			border-width: {{ $rBorderWidthMd ?? $rBorderWidth }}px;
		}
		.ribbon-horizontal span {
			font-size: {{ $rsFontSizeMd ?? $rsFontSize }}px;
			@if (config('lang.direction') == 'rtl')
				padding-right: {{ $rsPaddingStartMd ?? $rsPaddingStart }}px;
			@else
				padding-left: {{ $rsPaddingStartMd ?? $rsPaddingStart }}px;
			@endif
			top: {{ $rsTopMd ?? $rsTop }}px !important;
		}
	}
	@media (min-width: 576px) and (max-width: 767px) {
		.item-list .ribbon-horizontal {
			width: {{ $rWidthSm ?? $rWidth }}px !important;
			@if (config('lang.direction') == 'rtl')
				right: {{ $rLeftSm ?? $rLeft }}px !important;
			@else
				left: {{ $rLeftSm ?? $rLeft }}px !important;
			@endif
			top: {{ $rTopSm ?? $rTop }}px !important;
			border-width: {{ $rBorderWidthSm ?? $rBorderWidth }}px;
		}
		.ribbon-horizontal span {
			font-size: {{ $rsFontSizeSm ?? $rsFontSize }}px;
			@if (config('lang.direction') == 'rtl')
				padding-right: {{ $rsPaddingStartSm ?? $rsPaddingStart }}px;
			@else
				padding-left: {{ $rsPaddingStartSm ?? $rsPaddingStart }}px;
			@endif
			top: {{ $rsTopSm ?? $rsTop }}px !important;
		}
	}
	@media (max-width: 575px) {
		.item-list .ribbon-horizontal {
			width: {{ $rWidthXs ?? $rWidth }}px !important;
			@if (config('lang.direction') == 'rtl')
				right: {{ $rLeftXs ?? $rLeft }}px !important;
			@else
				left: {{ $rLeftXs ?? $rLeft }}px !important;
			@endif
			top: {{ $rTopXs ?? $rTop }}px !important;
			border-width: {{ $rBorderWidthXs ?? $rBorderWidth }}px;
		}
		.ribbon-horizontal span {
			font-size: {{ $rsFontSizeXs ?? $rsFontSize }}px;
			@if (config('lang.direction') == 'rtl')
				padding-right: {{ $rsPaddingStartXs ?? $rsPaddingStart }}px;
			@else
				padding-left: {{ $rsPaddingStartXs ?? $rsPaddingStart }}px;
			@endif
			top: {{ $rsTopXs ?? $rsTop }}px !important;
		}
	}

</style>