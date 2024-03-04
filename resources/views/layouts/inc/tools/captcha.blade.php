@php
	$captcha = config('settings.security.captcha');
	$isCaptchaEnabled = !empty($captcha);
	
	$label ??= false;
	$noLabel ??= false;
	$colLeft ??= null;
	$colRight ??= null;
@endphp
@if ($isCaptchaEnabled)
	@php
		$params = [];
		if (isset($label) && $label) {
			$params['label'] = $label;
		}
		if (isset($noLabel) && $noLabel) {
			$params['noLabel'] = $noLabel;
		}
		if (!empty($colLeft)) {
			$params['colLeft'] = $colLeft;
		}
		if (!empty($colRight)) {
			$params['colRight'] = $colRight;
		}
	@endphp
	@if ($captcha == 'recaptcha')
		@include('layouts.inc.tools.captcha.recaptcha', $params)
	@endif
	@if (in_array($captcha, ['default', 'math', 'flat', 'mini', 'inverse', 'custom']))
		@include('layouts.inc.tools.captcha.captcha', $params)
	@endif
@endif
