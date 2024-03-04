@php
	$sectionOptions = $getLatestListingsOp ?? [];
	$sectionData ??= [];
	$widget = (array)data_get($sectionData, 'latest');
	$widgetType = (data_get($sectionOptions, 'items_in_carousel') == '1') ? 'carousel' : 'normal';
@endphp
@includeFirst([
		config('larapen.core.customizedViewPath') . 'search.inc.posts.widget.' . $widgetType,
		'search.inc.posts.widget.' . $widgetType
	],
	['widget' => $widget, 'sectionOptions' => $sectionOptions]
)
