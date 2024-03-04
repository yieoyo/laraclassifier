@php
	$post ??= [];
	$fiTheme = config('larapen.core.fileinput.theme', 'bs5');
@endphp
<div class="modal fade" id="contactUser" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			
			<div class="modal-header px-3">
				<h4 class="modal-title">
					<i class="fas fa-envelope"></i> {{ t('contact_advertiser') }}
				</h4>
				
				<button type="button" class="close" data-bs-dismiss="modal">
					<span aria-hidden="true">&times;</span>
					<span class="sr-only">{{ t('Close') }}</span>
				</button>
			</div>
			
			<form role="form"
			      method="POST"
			      action="{{ url('account/messages/posts/' . data_get($post, 'id')) }}"
			      enctype="multipart/form-data"
			>
				{!! csrf_field() !!}
				@honeypot
				<div class="modal-body">

					@if (isset($errors) && $errors->any() && old('messageForm')=='1')
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
							<ul class="list list-check">
								@foreach($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif
					
					@php
						$authUser = auth()->check() ? auth()->user() : null;
						$isNameCanBeHidden = (!empty($authUser));
						$isEmailCanBeHidden = (!empty($authUser) && !empty($authUser->email));
						$isPhoneCanBeHidden = (!empty($authUser) && !empty($authUser->phone));
						$authFieldValue = data_get($post, 'auth_field', getAuthField());
					@endphp
					
					{{-- name --}}
					@if ($isNameCanBeHidden)
						<input type="hidden" name="name" value="{{ $authUser->name ?? null }}">
					@else
						@php
							$fromNameError = (isset($errors) && $errors->has('name')) ? ' is-invalid' : '';
						@endphp
						<div class="mb-3 required">
							<label class="control-label" for="name">{{ t('Name') }} <sup>*</sup></label>
							<div class="input-group">
								<input id="fromName" name="name"
									   type="text"
									   class="form-control{{ $fromNameError }}"
									   placeholder="{{ t('your_name') }}"
									   value="{{ old('name', $authUser->name ?? null) }}"
								>
							</div>
						</div>
					@endif
					
					{{-- email --}}
					@if ($isEmailCanBeHidden)
						<input type="hidden" name="email" value="{{ $authUser->email ?? null }}">
					@else
						@php
							$fromEmailError = (isset($errors) && $errors->has('email')) ? ' is-invalid' : '';
						@endphp
						<div class="mb-3 required">
							<label class="control-label" for="email">{{ t('E-mail') }}
								@if ($authFieldValue == 'email')
									<sup>*</sup>
								@endif
							</label>
							<div class="input-group">
								<span class="input-group-text"><i class="far fa-envelope"></i></span>
								<input id="fromEmail" name="email"
									   type="text"
									   class="form-control{{ $fromEmailError }}"
									   placeholder="{{ t('eg_email') }}"
									   value="{{ old('email', $authUser->email ?? null) }}"
								>
							</div>
						</div>
					@endif
					
					{{-- phone --}}
					@if ($isPhoneCanBeHidden)
						<input type="hidden" name="phone" value="{{ $authUser->phone ?? null }}">
						<input name="phone_country" type="hidden" value="{{ $authUser->phone_country ?? config('country.code') }}">
					@else
						@php
							$fromPhoneError = (isset($errors) && $errors->has('phone')) ? ' is-invalid' : '';
							$phoneValue = $authUser->phone ?? null;
							$phoneCountryValue = $authUser->phone_country ?? config('country.code');
							$phoneValue = phoneE164($phoneValue, $phoneCountryValue);
							$phoneValueOld = phoneE164(old('phone', $phoneValue), old('phone_country', $phoneCountryValue));
						@endphp
						<div class="mb-3 required">
							<label class="control-label" for="phone">{{ t('phone_number') }}
								@if ($authFieldValue == 'phone')
									<sup>*</sup>
								@endif
							</label>
							<input id="fromPhone" name="phone"
								   type="tel"
								   maxlength="60"
								   class="form-control m-phone{{ $fromPhoneError }}"
								   placeholder="{{ t('phone_number') }}"
								   value="{{ $phoneValueOld }}"
							>
							<input name="phone_country" type="hidden" value="{{ old('phone_country', $phoneCountryValue) }}">
						</div>
					@endif
					
					{{-- auth_field --}}
					<input name="auth_field" type="hidden" value="{{ $authFieldValue }}">
					
					{{-- body --}}
					<?php $bodyError = (isset($errors) && $errors->has('body')) ? ' is-invalid' : ''; ?>
					<div class="mb-3 required">
						<label class="control-label" for="body">
							{{ t('Message') }} <span class="text-count">(500 max)</span> <sup>*</sup>
						</label>
						<textarea id="body" name="body"
							rows="5"
							class="form-control required{{ $bodyError }}"
							style="height: 150px;"
							placeholder="{{ t('your_message_here') }}"
						>{{ old('body', t('is_still_available', ['name' => data_get($post, 'contact_name', t('sir_miss'))])) }}</textarea>
					</div>
					@php
						$catType = data_get($post, 'category.parent.type', data_get($post, 'category.type'));
					@endphp
					@if ($catType == 'job-offer')
						{{-- filename --}}
						<?php $filenameError = (isset($errors) && $errors->has('filename')) ? ' is-invalid' : ''; ?>
						<div class="mb-3 required" {!! (config('lang.direction')=='rtl') ? 'dir="rtl"' : '' !!}>
							<label class="control-label{{ $filenameError }}" for="filename">{{ t('Resume') }} </label>
							<input id="filename" name="filename" type="file" class="file{{ $filenameError }}">
							<div class="form-text text-muted">
								{{ t('file_types', ['file_types' => showValidFileTypes('file')]) }}
							</div>
						</div>
						<input type="hidden" name="catType" value="{{ $catType }}">
					@endif
					
					@include('layouts.inc.tools.captcha', ['label' => true])
					
					<input type="hidden" name="country_code" value="{{ config('country.code') }}">
					<input type="hidden" name="post_id" value="{{ data_get($post, 'id') }}">
					<input type="hidden" name="messageForm" value="1">
				</div>
				
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary float-end">{{ t('send_message') }}</button>
					<button type="button" class="btn btn-default" data-bs-dismiss="modal">{{ t('Cancel') }}</button>
				</div>
			</form>
			
		</div>
	</div>
</div>
@section('after_styles')
	@parent
	<link href="{{ url('assets/plugins/bootstrap-fileinput/css/fileinput.min.css') }}" rel="stylesheet">
	@if (config('lang.direction') == 'rtl')
		<link href="{{ url('assets/plugins/bootstrap-fileinput/css/fileinput-rtl.min.css') }}" rel="stylesheet">
	@endif
	@if (str_starts_with($fiTheme, 'explorer'))
		<link href="{{ url('assets/plugins/bootstrap-fileinput/themes/' . $fiTheme . '/theme.min.css') }}" rel="stylesheet">
	@endif
	<style>
		.krajee-default.file-preview-frame:hover:not(.file-preview-error) {
			box-shadow: 0 0 5px 0 #666666;
		}
		.file-loading:before {
			content: " {{ t('loading_wd') }}";
		}
	</style>
@endsection

@section('after_scripts')
    @parent
	
	<script src="{{ url('assets/plugins/bootstrap-fileinput/js/plugins/sortable.min.js') }}" type="text/javascript"></script>
	<script src="{{ url('assets/plugins/bootstrap-fileinput/js/fileinput.min.js') }}" type="text/javascript"></script>
	<script src="{{ url('assets/plugins/bootstrap-fileinput/themes/' . $fiTheme . '/theme.js') }}" type="text/javascript"></script>
	<script src="{{ url('common/js/fileinput/locales/' . config('app.locale') . '.js') }}" type="text/javascript"></script>

	<script>
		@if (auth()->check())
			phoneCountry = '{{ old('phone_country', ($phoneCountryValue ?? '')) }}';
		@endif
		
		let options = {};
		options.theme = '{{ $fiTheme }}';
		options.language = '{{ config('app.locale') }}';
		options.rtl = {{ (config('lang.direction') == 'rtl') ? 'true' : 'false' }};
		options.allowedFileExtensions = {!! getUploadFileTypes('file', true) !!};
		options.minFileSize = {{ (int)config('settings.upload.min_file_size', 0) }};
		options.maxFileSize = {{ (int)config('settings.upload.max_file_size', 1000) }};
		options.showPreview = false;
		options.showUpload = false;
		options.showRemove = false;
		
		{{-- fileinput (resume) --}}
		$('#filename').fileinput(options);
		
		$(document).ready(function () {
			@if ($errors->any())
				@if ($errors->any() && old('messageForm')=='1')
					{{-- Re-open the modal if error occured --}}
					let quickLogin = new bootstrap.Modal(document.getElementById('contactUser'), {});
					quickLogin.show();
				@endif
			@endif
		});
	</script>
@endsection
