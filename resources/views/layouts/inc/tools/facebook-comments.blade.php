@php
	$commentsAreDisabledByUser ??= false;
	$areCommentsActivated = (
		config('settings.single.activation_facebook_comments')
		&& config('services.facebook.client_id')
		&& !$commentsAreDisabledByUser
	);
	$fbClientId = config('services.facebook.client_id');
	$locale = config('lang.iso_locale', 'en_US');
@endphp
@if ($areCommentsActivated)
	<div class="container">
		<div id="fb-root"></div>
		<script>
			(function (d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s);
				js.id = id;
				js.src = "//connect.facebook.net/{{ $locale }}/sdk.js#xfbml=1&version=v2.5&appId={{ $fbClientId }}";
				fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));
		</script>
		<div class="fb-comments" data-href="{{ request()->url() }}" data-width="100%" data-numposts="5"></div>
	</div>
@endif
