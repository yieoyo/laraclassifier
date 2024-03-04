<?php
$sectionOptions = $getTextAreaOp ?? [];
$sectionData ??= [];

// Fallback Language
$textTitle = data_get($sectionOptions, 'title_' . config('appLang.code'));
$textTitle = replaceGlobalPatterns($textTitle);

$textBody = data_get($sectionOptions, 'body_' . config('appLang.code'));
$textBody = replaceGlobalPatterns($textBody);

// Current Language
if (!empty(data_get($sectionOptions, 'title_' . config('app.locale')))) {
	$textTitle = data_get($sectionOptions, 'title_' . config('app.locale'));
	$textTitle = replaceGlobalPatterns($textTitle);
}

if (!empty(data_get($sectionOptions, 'body_' . config('app.locale')))) {
	$textBody = data_get($sectionOptions, 'body_' . config('app.locale'));
	$textBody = replaceGlobalPatterns($textBody);
}

$hideOnMobile = (data_get($sectionOptions, 'hide_on_mobile') == '1') ? ' hidden-sm' : '';
?>
@if (!empty($textTitle) || !empty($textBody))
	@includeFirst([config('larapen.core.customizedViewPath') . 'home.inc.spacer', 'home.inc.spacer'], ['hideOnMobile' => $hideOnMobile])
	<div class="container{{ $hideOnMobile }}">
		<div class="card">
			<div class="card-body">
				@if (!empty($textTitle))
					<h2 class="card-title">{{ $textTitle }}</h2>
				@endif
				@if (!empty($textBody))
					<div>{!! $textBody !!}</div>
				@endif
			</div>
		</div>
	</div>
@endif
