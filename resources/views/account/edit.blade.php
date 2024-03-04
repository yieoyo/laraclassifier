{{--
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
--}}
@extends('layouts.master')

@php
	$authUserIsAdmin ??= false;
	$stats ??= [];
	$countThreads = data_get($stats, 'threads.all') ?? 0;
	$postsVisits = data_get($stats, 'posts.visits') ?? 0;
	$countPosts = (data_get($stats, 'posts.published') ?? 0)
		+ (data_get($stats, 'posts.archived') ?? 0)
		+ (data_get($stats, 'posts.pendingApproval') ?? 0);
	$countFavoritePosts = data_get($stats, 'posts.favourite') ?? 0;
	
	$fiTheme = config('larapen.core.fileinput.theme', 'bs5');
@endphp

@section('content')
	@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
	<div class="main-container">
		<div class="container">
			<div class="row">
				<div class="col-md-3 page-sidebar">
					@includeFirst([config('larapen.core.customizedViewPath') . 'account.inc.sidebar', 'account.inc.sidebar'])
				</div>

				<div class="col-md-9 page-content">

					@include('flash::message')

					@if (isset($errors) && $errors->any())
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
							<h5><strong>{{ t('oops_an_error_has_occurred') }}</strong></h5>
							<ul class="list list-check">
								@foreach ($errors->all() as $error)
									<li>{!! $error !!}</li>
								@endforeach
							</ul>
						</div>
					@endif
					
					<div id="avatarUploadError" class="center-block" style="width:100%; display:none"></div>
					<div id="avatarUploadSuccess" class="alert alert-success fade show" style="display:none;"></div>
					
					<div class="inner-box default-inner-box">
						<div class="row">
							<div class="col-md-5 col-sm-4 col-12">
								<h3 class="no-padding text-center-480 useradmin">
									<a href="">
										<img id="userImg" class="userImg" src="{{ $authUser->photo_url }}" alt="user">&nbsp;
										{{ $authUser->name }}
									</a>
								</h3>
							</div>
							<div class="col-md-7 col-sm-8 col-12">
								<div class="header-data text-center-xs">
									{{-- Threads Stats --}}
									<div class="hdata">
										<div class="mcol-left">
											<i class="fas fa-envelope ln-shadow"></i>
										</div>
										<div class="mcol-right">
											{{-- Number of messages --}}
											<p>
												<a href="{{ url('account/messages') }}">
													{{ \App\Helpers\Number::short($countThreads ?? 0) }}
													<em>{{ trans_choice('global.count_mails', getPlural($countThreads ?? 0), [], config('app.locale')) }}</em>
												</a>
											</p>
										</div>
										<div class="clearfix"></div>
									</div>
									
									{{-- Traffic Stats --}}
									<div class="hdata">
										<div class="mcol-left">
											<i class="fa fa-eye ln-shadow"></i>
										</div>
										<div class="mcol-right">
											{{-- Number of visitors --}}
											<p>
												<a href="{{ url('account/posts/list') }}">
													{{ \App\Helpers\Number::short($postsVisits ?? 0) }}
													<em>{{ trans_choice('global.count_visits', getPlural($postsVisits ?? 0), [], config('app.locale')) }}</em>
												</a>
											</p>
										</div>
										<div class="clearfix"></div>
									</div>

									{{-- Listings Stats --}}
									<div class="hdata">
										<div class="mcol-left">
											<i class="fas fa-bullhorn ln-shadow"></i>
										</div>
										<div class="mcol-right">
											{{-- Number of listings --}}
											<p>
												<a href="{{ url('account/posts/list') }}">
													{{ \App\Helpers\Number::short($countPosts ?? 0) }}
													<em>{{ trans_choice('global.count_listings', getPlural($countPosts ?? 0), [], config('app.locale')) }}</em>
												</a>
											</p>
										</div>
										<div class="clearfix"></div>
									</div>

									{{-- Favorites Stats --}}
									<div class="hdata">
										<div class="mcol-left">
											<i class="fa fa-user ln-shadow"></i>
										</div>
										<div class="mcol-right">
											{{-- Number of favorites --}}
											<p>
												<a href="{{ url('account/posts/favourite') }}">
													{{ \App\Helpers\Number::short($countFavoritePosts ?? 0) }}
													<em>
														{{ trans_choice(
																'global.count_favorites',
																getPlural($countFavoritePosts ?? 0),
																[],
																config('app.locale')
														) }}
													</em>
												</a>
											</p>
										</div>
										<div class="clearfix"></div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="inner-box default-inner-box" style="overflow: visible;">
						<div class="welcome-msg">
							<h3 class="page-sub-header2 clearfix no-padding">{{ t('Hello') }} {{ $authUser->name }} ! </h3>
							<span class="page-sub-header-sub small">
                                {{ t('You last logged in at') }}: {{ \App\Helpers\Date::format($authUser->last_login_at, 'datetime') }}
                            </span>
						</div>
						
						<div id="accordion" class="panel-group">
							{{-- PHOTO --}}
							<div class="card card-default">
								<div class="card-header">
									<h4 class="card-title">
										<a href="#photoPanel" data-bs-toggle="collapse" data-parent="#accordion">{{ t('Photo or Avatar') }}</a>
									</h4>
								</div>
								@php
									$photoPanelClass = '';
									$photoPanelClass = request()->filled('panel')
										? (request()->query('panel') == 'photo' ? 'show' : $photoPanelClass)
										: ((old('panel')=='' || old('panel') =='photo') ? 'show' : $photoPanelClass);
								@endphp
								<div class="panel-collapse collapse {{ $photoPanelClass }}" id="photoPanel">
									<div class="card-body">
										<form name="photoUpdate" class="form-horizontal" role="form" method="POST" action="{{ url('account/photo') }}">
											<div class="row">
												<div class="col-xl-12 text-center">
													
													@php
														$photoError = (isset($errors) && $errors->has('photo')) ? ' is-invalid' : '';
													@endphp
													<div class="photo-field">
														<div class="file-loading">
															<input id="photoField" name="photo" type="file" class="file {{ $photoError }}">
														</div>
													</div>
												
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
							
							{{-- USER --}}
							<div class="card card-default">
								<div class="card-header">
									<h4 class="card-title">
										<a href="#userPanel" aria-expanded="true" data-bs-toggle="collapse" data-parent="#accordion">
											{{ t('Account Details') }}
										</a>
									</h4>
								</div>
								@php
									$userPanelClass = '';
									$userPanelClass = request()->filled('panel')
										? (request()->query('panel') == 'user' ? 'show' : $userPanelClass)
										: ((old('panel') == '' || old('panel') == 'user') ? 'show' : $userPanelClass);
								@endphp
								<div class="panel-collapse collapse {{ $userPanelClass }}" id="userPanel">
									<div class="card-body">
										<form name="details"
											  class="form-horizontal"
											  role="form"
											  method="POST"
											  action="{{ url('account') }}"
											  enctype="multipart/form-data"
										>
											{!! csrf_field() !!}
											<input name="_method" type="hidden" value="PUT">
											<input name="panel" type="hidden" value="user">

											{{-- gender_id --}}
											<?php $genderIdError = (isset($errors) && $errors->has('gender_id')) ? ' is-invalid' : ''; ?>
											<div class="row mb-3 required">
												<label class="col-md-3 col-form-label" for="gender_id">{{ t('gender') }}</label>
												<div class="col-md-9 col-lg-8 col-xl-6">
													<select name="gender_id" id="genderId" class="form-control selecter{{ $genderIdError }}">
														<option value="0" @selected(empty(old('gender_id')))>
															{{ t('Select') }}
														</option>
														@if ($genders->count() > 0)
															@foreach ($genders as $gender)
																<option value="{{ $gender->id }}"
																		@selected(old('gender_id', $authUser->gender_id) == $gender->id)
																>
																	{{ $gender->name }}
																</option>
															@endforeach
														@endif
													</select>
												</div>
											</div>
											
											{{-- name --}}
											<?php $nameError = (isset($errors) && $errors->has('name')) ? ' is-invalid' : ''; ?>
											<div class="row mb-3 required">
												<label class="col-md-3 col-form-label{{ $nameError }}" for="name">{{ t('Name') }} <sup>*</sup></label>
												<div class="col-md-9 col-lg-8 col-xl-6">
													<input name="name"
													       type="text"
													       class="form-control{{ $nameError }}"
													       placeholder=""
													       value="{{ old('name', $authUser->name) }}"
													>
												</div>
											</div>
											
											{{-- username --}}
											<?php $usernameError = (isset($errors) && $errors->has('username')) ? ' is-invalid' : ''; ?>
											<div class="row mb-3 required">
												<label class="col-md-3 col-form-label{{ $usernameError }}" for="username">{{ t('Username') }}</label>
												<div class="col-md-9 col-lg-8 col-xl-6">
													<div class="input-group">
														<span class="input-group-text"><i class="far fa-user"></i></span>
														<input id="username" name="username"
															   type="text"
															   class="form-control{{ $usernameError }}"
															   placeholder="{{ t('Username') }}"
															   value="{{ old('username', $authUser->username) }}"
														>
													</div>
												</div>
											</div>
											
											{{-- auth_field (as notification channel) --}}
											@php
												$authFields = getAuthFields(true);
												$authFieldError = (isset($errors) && $errors->has('auth_field')) ? ' is-invalid' : '';
												$usersCanChooseNotifyChannel = isUsersCanChooseNotifyChannel(true);
												$authFieldValue = $authUser->auth_field ?? getAuthField();
												$authFieldValue = ($usersCanChooseNotifyChannel) ? old('auth_field', $authFieldValue) : $authFieldValue;
											@endphp
											@if ($usersCanChooseNotifyChannel)
												<div class="row mb-3 required">
													<label class="col-md-3 col-form-label" for="auth_field">
														{{ t('notifications_channel') }} <sup>*</sup>
													</label>
													<div class="col-md-9">
														@foreach ($authFields as $iAuthField => $notificationType)
															<div class="form-check form-check-inline pt-2">
																<input name="auth_field"
																	   id="{{ $iAuthField }}AuthField"
																	   value="{{ $iAuthField }}"
																	   class="form-check-input auth-field-input{{ $authFieldError }}"
																	   type="radio" @checked($authFieldValue == $iAuthField)
																>
																<label class="form-check-label mb-0" for="{{ $iAuthField }}AuthField">
																	{{ $notificationType }}
																</label>
															</div>
														@endforeach
														<div class="form-text text-muted">
															{{ t('notifications_channel_hint') }}
														</div>
													</div>
												</div>
											@else
												<input id="{{ $authFieldValue }}AuthField" name="auth_field" type="hidden" value="{{ $authFieldValue }}">
											@endif
											
											@php
												$forceToDisplay = isBothAuthFieldsCanBeDisplayed() ? ' force-to-display' : '';
											@endphp
											
											{{-- email --}}
											@php
												$emailError = (isset($errors) && $errors->has('email')) ? ' is-invalid' : '';
											@endphp
											<div class="row mb-3 auth-field-item required{{ $forceToDisplay }}">
												<label class="col-md-3 col-form-label{{ $emailError }}" for="email">{{ t('email') }}
													@if (getAuthField() == 'email')
														<sup>*</sup>
													@endif
												</label>
												<div class="col-md-9 col-lg-8 col-xl-6">
													<div class="input-group">
														<span class="input-group-text"><i class="far fa-envelope"></i></span>
														<input id="email" name="email"
															   type="email"
															   class="form-control{{ $emailError }}"
															   placeholder="{{ t('email_address') }}"
															   value="{{ old('email', $authUser->email) }}"
														>
													</div>
												</div>
											</div>
											
											{{-- phone --}}
											@php
												$phoneError = (isset($errors) && $errors->has('phone')) ? ' is-invalid' : '';
												$phoneValue = $authUser->phone ?? null;
												$phoneCountryValue = $authUser->phone_country ?? config('country.code');
												$phoneValue = phoneE164($phoneValue, $phoneCountryValue);
												$phoneValueOld = phoneE164(old('phone', $phoneValue), old('phone_country', $phoneCountryValue));
											@endphp
											<div class="row mb-3 auth-field-item required{{ $forceToDisplay }}">
												<label class="col-md-3 col-form-label{{ $phoneError }}" for="phone">{{ t('phone') }}
													@if (getAuthField() == 'phone')
														<sup>*</sup>
													@endif
												</label>
												<div class="col-md-9 col-lg-8 col-xl-6">
													<div class="input-group">
														<input id="phone" name="phone"
															   type="tel"
															   class="form-control{{ $phoneError }}"
															   value="{{ $phoneValueOld }}"
														>
														<span class="input-group-text iti-group-text">
															<input name="phone_hidden" id="phoneHidden" type="checkbox"
																   value="1" @checked(old('phone_hidden', $authUser->phone_hidden) == '1')>&nbsp;
															<small>{{ t('Hide') }}</small>
														</span>
													</div>
													<input name="phone_country" type="hidden" value="{{ old('phone_country', $phoneCountryValue) }}">
												</div>
											</div>
											
											{{-- country_code --}}
											<input name="country_code" type="hidden" value="{{ $authUser->country_code }}">

											<div class="row mb-3">
												<div class="offset-md-3 col-md-9"></div>
											</div>
											
											{{-- button --}}
											<div class="row">
												<div class="offset-md-3 col-md-9">
													<button type="submit" class="btn btn-primary">{{ t('Update') }}</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
							
							{{-- SETTINGS --}}
							<div class="card card-default">
								<div class="card-header">
									<h4 class="card-title"><a href="#settingsPanel" data-bs-toggle="collapse" data-parent="#accordion">{{ t('Settings') }}</a></h4>
								</div>
								@php
									$settingsPanelClass = '';
									$settingsPanelClass = request()->filled('panel')
										? (request()->query('panel') == 'settings' ? 'show' : $settingsPanelClass)
										: ((old('panel') == 'settings') ? 'show' : $settingsPanelClass);
								@endphp
								<div class="panel-collapse collapse {{ $settingsPanelClass }}" id="settingsPanel">
									<div class="card-body">
										<form name="settings"
											  class="form-horizontal"
											  role="form"
											  method="POST"
											  action="{{ url('account/settings') }}"
											  enctype="multipart/form-data"
										>
											{!! csrf_field() !!}
											<input name="_method" type="hidden" value="PUT">
											<input name="panel" type="hidden" value="settings">
											
											<input name="gender_id" type="hidden" value="{{ $authUser->gender_id }}">
											<input name="name" type="hidden" value="{{ $authUser->name }}">
											<input name="phone" type="hidden" value="{{ $authUser->phone }}">
											<input name="phone_country" type="hidden" value="{{ $authUser->phone_country }}">
											<input name="email" type="hidden" value="{{ $authUser->email }}">
										
											@if (config('settings.single.activation_facebook_comments') && config('services.facebook.client_id'))
												{{-- disable_comments --}}
												<div class="row mb-3">
													<label class="col-md-3 col-form-label"></label>
													<div class="col-md-9">
														<div class="form-check pt-2">
															<input id="disableComments" name="disable_comments"
																   class="form-check-input"
																   value="1"
																   type="checkbox" @checked($authUser->disable_comments == 1)
															>
															<label class="form-check-label" for="disable_comments" style="font-weight: normal;">
																{{ t('Disable comments on my listings') }}
															</label>
														</div>
													</div>
												</div>
											@endif
											
											{{-- password --}}
											<?php $passwordError = (isset($errors) && $errors->has('password')) ? ' is-invalid' : ''; ?>
											<div class="row mb-2">
												<label class="col-md-3 col-form-label{{ $passwordError }}">{{ t('New Password') }}</label>
												<div class="col-md-9 col-lg-8 col-xl-6">
													<input id="password" name="password"
														   type="password"
														   class="form-control{{ $passwordError }}"
														   placeholder="{{ t('password') }}"
														   autocomplete="new-password"
													>
												</div>
											</div>
											
											{{-- password_confirmation --}}
											<?php $passwordError = (isset($errors) && $errors->has('password')) ? ' is-invalid' : ''; ?>
											<div class="row mb-3">
												<label class="col-md-3 col-form-label{{ $passwordError }}">{{ t('Confirm Password') }}</label>
												<div class="col-md-9 col-lg-8 col-xl-6">
													<input id="password_confirmation" name="password_confirmation"
														   type="password"
														   class="form-control{{ $passwordError }}"
														   placeholder="{{ t('Confirm Password') }}"
													>
												</div>
											</div>
											
											@if ($authUser->accept_terms != 1)
												{{-- accept_terms --}}
												<?php $acceptTermsError = (isset($errors) && $errors->has('accept_terms')) ? ' is-invalid' : ''; ?>
												<div class="row mb-1 required">
													<label class="col-md-3 col-form-label"></label>
													<div class="col-md-9">
														<div class="form-check">
															<input name="accept_terms" id="acceptTerms"
																   class="form-check-input{{ $acceptTermsError }}"
																   value="1"
																   type="checkbox" @checked(old('accept_terms', $authUser->accept_terms) == '1')
															>
															<label class="form-check-label" for="acceptTerms" style="font-weight: normal;">
																{!! t('accept_terms_label', ['attributes' => getUrlPageByType('terms')]) !!}
															</label>
														</div>
														<div style="clear:both"></div>
													</div>
												</div>
												
												<input type="hidden" name="user_accept_terms" value="{{ (int)$authUser->accept_terms }}">
											@endif
											
											{{-- accept_marketing_offers --}}
											<?php $acceptMarketingOffersError = (isset($errors) && $errors->has('accept_marketing_offers')) ? ' is-invalid' : ''; ?>
											<div class="row mb-3 required">
												<label class="col-md-3 col-form-label"></label>
												<div class="col-md-9">
													<div class="form-check">
														<input name="accept_marketing_offers" id="acceptMarketingOffers"
															   class="form-check-input{{ $acceptMarketingOffersError }}"
															   value="1"
															   type="checkbox" @checked(old('accept_marketing_offers', $authUser->accept_marketing_offers) == '1')
														>
														<label class="form-check-label" for="acceptMarketingOffers" style="font-weight: normal;">
															{!! t('accept_marketing_offers_label') !!}
														</label>
													</div>
													<div style="clear:both"></div>
												</div>
											</div>
											
											{{-- time_zone --}}
											<?php $timeZoneError = (isset($errors) && $errors->has('time_zone')) ? ' is-invalid' : ''; ?>
											<div class="row mb-4 required">
												<label class="col-md-3 col-form-label{{ $timeZoneError }}" for="time_zone">
													{{ t('preferred_time_zone_label') }}
												</label>
												<div class="col-md-9 col-lg-8 col-xl-6">
													<select name="time_zone" class="form-control large-data-selecter{{ $timeZoneError }}">
														<option value="" @selected(empty(old('time_zone')))>
															{{ t('select_a_time_zone') }}
														</option>
														@php
															$tz = !empty($authUser->time_zone) ? $authUser->time_zone : '';
														@endphp
														@foreach (\App\Helpers\Date::getTimeZones() as $key => $item)
															<option value="{{ $key }}" @selected(old('time_zone', $tz) == $key)>
																{{ $item }}
															</option>
														@endforeach
													</select>
													<div class="form-text text-muted">
														@if ($authUserIsAdmin)
														{!! t('admin_preferred_time_zone_info', [
																'frontTz' => config('country.time_zone'),
																'country' => config('country.name'),
																'adminTz' => config('app.timezone'),
															]) !!}
														@else
															{!! t('preferred_time_zone_info', [
																'frontTz' => config('country.time_zone'),
																'country' => config('country.name'),
															]) !!}
														@endif
													</div>
												</div>
											</div>
											
											{{-- button --}}
											<div class="row">
												<div class="offset-md-3 col-md-9">
													<button type="submit" class="btn btn-primary">{{ t('Update') }}</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>

						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('after_styles')
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
	<style>
		/* Avatar Upload */
		.photo-field {
			display: inline-block;
			vertical-align: middle;
		}
		.photo-field .krajee-default.file-preview-frame,
		.photo-field .krajee-default.file-preview-frame:hover {
			margin: 0;
			padding: 0;
			border: none;
			box-shadow: none;
			text-align: center;
		}
		.photo-field .file-input {
			display: table-cell;
			width: 150px;
		}
		.photo-field .krajee-default.file-preview-frame .kv-file-content {
			width: 150px;
			height: 160px;
		}
		.kv-reqd {
			color: red;
			font-family: monospace;
			font-weight: normal;
		}
		
		.file-preview {
			padding: 2px;
		}
		.file-drop-zone {
			margin: 2px;
			min-height: 100px;
		}
		.file-drop-zone .file-preview-thumbnails {
			cursor: pointer;
		}
		
		.krajee-default.file-preview-frame .file-thumbnail-footer {
			height: 30px;
		}
		
		/* Allow clickable uploaded photos (Not possible) */
		.file-drop-zone {
			padding: 20px;
		}
		.file-drop-zone .kv-file-content {
			padding: 0
		}
	</style>
@endsection

@section('after_scripts')
	<script src="{{ url('assets/plugins/bootstrap-fileinput/js/plugins/sortable.min.js') }}" type="text/javascript"></script>
	<script src="{{ url('assets/plugins/bootstrap-fileinput/js/fileinput.min.js') }}" type="text/javascript"></script>
	<script src="{{ url('assets/plugins/bootstrap-fileinput/themes/' . $fiTheme . '/theme.min.js') }}" type="text/javascript"></script>
	<script src="{{ url('common/js/fileinput/locales/' . config('app.locale') . '.js') }}" type="text/javascript"></script>
	<script>
		phoneCountry = '{{ old('phone_country', ($phoneCountryValue ?? '')) }}';
		
		let defaultAvatarUrl = '{{ imgUrl(config('larapen.core.avatar.default')) }}';
		let defaultAvatarAlt = '{{ t('Your Photo or Avatar') }}';
		let uploadHint = '<h6 class="text-muted pb-0">{{ t('Click to select') }}</h6>';
		
		let options = {};
		options.theme = '{{ $fiTheme }}';
		options.language = '{{ config('app.locale') }}';
		options.rtl = {{ (config('lang.direction') == 'rtl') ? 'true' : 'false' }};
		options.overwriteInitial = true;
		options.showCaption = false;
		options.showPreview = true;
		options.allowedFileExtensions = {!! getUploadFileTypes('image', true) !!};
		options.uploadUrl = '{{ url('account/photo') }}';
		options.uploadExtraData = {
			_token:'{{ csrf_token() }}',
			_method:'PUT'
		};
		options.showClose = false;
		options.showBrowse = true;
		options.browseClass = 'btn btn-primary';
		options.minFileSize = {{ (int)config('settings.upload.min_image_size', 0) }};
		options.maxFileSize = {{ (int)config('settings.upload.max_image_size', 1000) }};
		options.uploadAsync = false;
		options.browseOnZoneClick = true;
		options.minFileCount = 0;
		options.maxFileCount = 1;
		options.validateInitialCount = true;
		options.defaultPreviewContent = '<img src="' + defaultAvatarUrl + '" alt="' + defaultAvatarAlt + '">' + uploadHint;
		options.initialPreview = [];
		options.initialPreviewAsData = true;
		options.initialPreviewFileType = 'image';
		options.initialPreviewConfig = [];
		options.fileActionSettings = {
			showDrag: false,
			showRemove: true,
			removeClass: 'btn btn-outline-danger btn-sm',
			showZoom: true,
			zoomClass: 'btn btn-outline-secondary btn-sm'
		};
		options.elErrorContainer = '#avatarUploadError';
		options.msgErrorClass = 'alert alert-block alert-danger';
		options.layoutTemplates = {
			main2: '{preview}\n<div class="kv-upload-progress hide"></div>\n{browse}',
			footer: '<div class="file-thumbnail-footer pt-2">\n{actions}\n</div>',
			actions: '<div class="file-actions">\n'
				+ '<div class="file-footer-buttons">\n{delete} {zoom}</div>\n'
				+ '<div class="clearfix"></div>\n'
				+ '</div>'
		};
		
		@if (!empty($authUser->photo) && !empty($authUser->photo_url))
			@php
				try {
					$fileSize = (isset($disk) && $disk->exists($authUser->photo)) ? (int)$disk->size($authUser->photo) : 0;
				} catch (\Throwable $e) {
					$fileSize = 0;
				}
			@endphp
			options.initialPreview[0] = '{{ $authUser->photo_url }}';
			options.initialPreviewConfig[0] = {};
			options.initialPreviewConfig[0].key = {{ (int)$authUser->id }};
			options.initialPreviewConfig[0].caption = '{{ basename($authUser->photo) }}';
			options.initialPreviewConfig[0].size = {{ $fileSize }};
			options.initialPreviewConfig[0].url = '{{ url('account/photo/delete') }}';
			options.initialPreviewConfig[0].extra = options.uploadExtraData;
		@endif
		
		{{-- fileinput --}}
		let photoFieldEl = $('#photoField');
		photoFieldEl.fileinput(options);
		
		/* Auto-upload added file */
		photoFieldEl.on('filebatchselected', function(event, files) {
			$(this).fileinput('upload');
		});
		
		/* Show the upload status message */
		photoFieldEl.on('filebatchpreupload', function(event, data) {
			$('#avatarUploadSuccess').html('<ul></ul>').hide();
		});
		
		/* Show the success upload message */
		photoFieldEl.on('filebatchuploadsuccess', function(event, data) {
			/* Show uploads success messages */
			let out = '';
			$.each(data.files, function(key, file) {
				if (typeof file !== 'undefined') {
					let fname = file.name;
					out = out + {!! t('fileinput_file_uploaded_successfully') !!};
				}
			});
			let avatarUploadSuccessEl = $('#avatarUploadSuccess');
			avatarUploadSuccessEl.find('ul').append(out);
			avatarUploadSuccessEl.fadeIn('slow');
			
			$('#userImg').attr({'src':$('.photo-field .kv-file-content .file-preview-image').attr('src')});
		});
		
		/* Delete picture */
		photoFieldEl.on('filepredelete', function(event, key, jqXHR, data) {
			let abort = true;
			if (confirm("{{ t('Are you sure you want to delete this picture') }}")) {
				abort = false;
			}
			
			return abort;
		});
		
		photoFieldEl.on('filedeleted', function(event, key, jqXHR, data) {
			$('#userImg').attr({'src': defaultAvatarUrl});
			
			let out = "{{ t('Your photo or avatar has been deleted') }}";
			let avatarUploadSuccessEl = $('#avatarUploadSuccess');
			avatarUploadSuccessEl.html('<ul><li></li></ul>').hide();
			avatarUploadSuccessEl.find('ul li').append(out);
			avatarUploadSuccessEl.fadeIn('slow');
		});
	</script>
@endsection
