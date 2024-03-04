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

@section('wizard')
    @includeFirst([
		config('larapen.core.customizedViewPath') . 'post.createOrEdit.multiSteps.inc.wizard',
		'post.createOrEdit.multiSteps.inc.wizard'
	])
@endsection

@php
	$nextStepUrl ??= '/';
	$post ??= [];
	
	/* The Next Step URL */
	$nextStepUrl = url($nextStepUrl);
	$nextStepUrl = qsUrl($nextStepUrl, request()->only(['package']), null, false);
	
	$picturesLimit ??= 0;
	$picturesLimit = is_numeric($picturesLimit) ? $picturesLimit : 0;
	$picturesLimit = ($picturesLimit > 0) ? $picturesLimit : 1;
	
	// Get the listing pictures (by applying the picture limit)
	$pictures = data_get($post, 'pictures', []);
	$pictures = collect($pictures)->slice(0, $picturesLimit)->all();
	
	$fiTheme = config('larapen.core.fileinput.theme', 'bs5');
@endphp
@section('content')
	@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
    <div class="main-container">
        <div class="container">
            <div class="row">
    
                @includeFirst([config('larapen.core.customizedViewPath') . 'post.inc.notification', 'post.inc.notification'])
                
                <div class="col-md-12 page-content">
                    <div class="inner-box">
						
                        <h2 class="title-2">
							<strong><i class="fas fa-camera"></i> {{ t('Photos') }}</strong>
	                        @php
		                        try {
									if (auth()->check()) {
										if (auth()->user()->can(\App\Models\Permission::getStaffPermissions())) {
											$postLink = '-&nbsp;<a href="' . \App\Helpers\UrlGen::post($post) . '"
													  class=""
													  data-bs-placement="top"
													  data-bs-toggle="tooltip"
													  title="' . data_get($post, 'title') . '"
											>' . str(data_get($post, 'title'))->limit(45) . '</a>';
											
											echo $postLink;
										}
									}
								} catch (\Throwable $e) {}
	                        @endphp
						</h2>
						
                        <div class="row">
                            <div class="col-md-12">
                                <form class="form-horizontal"
                                      id="payableForm"
                                      method="POST"
                                      action="{{ request()->fullUrl() }}"
                                      enctype="multipart/form-data"
                                >
                                    {!! csrf_field() !!}
                                    <input type="hidden" name="post_id" value="{{ data_get($post, 'id') }}">
                                    <fieldset>
                                        @if (isset($picturesLimit) && is_numeric($picturesLimit) && $picturesLimit > 0)
											{{-- pictures --}}
											<?php $picturesError = (isset($errors) && $errors->has('pictures')) ? ' is-invalid' : ''; ?>
                                            <div id="picturesBloc" class="input-group row">
												<div class="col-md-3 form-label{{ $picturesError }}"> {{ t('pictures') }} </div>
												<div class="col-md-8"></div>
												<div class="col-md-12 text-center pt-2" style="position: relative; float: {!! (config('lang.direction')=='rtl') ? 'left' : 'right' !!};">
													<div {!! (config('lang.direction')=='rtl') ? 'dir="rtl"' : '' !!} class="file-loading">
														<input id="pictureField"
														       name="pictures[]"
														       type="file"
														       multiple
														       class="file picimg{{ $picturesError }}"
														>
													</div>
													<div class="form-text text-muted">
														{{ t('add_up_to_x_pictures_text', ['pictures_number' => $picturesLimit]) }}
													</div>
												</div>
                                            </div>
                                        @endif
                                        <div id="uploadError" class="mt-2" style="display: none;"></div>
                                        <div id="uploadSuccess" class="alert alert-success fade show mt-2" style="display: none;"></div>
										
										{{-- button --}}
                                        <div class="input-group row mt-4">
                                            <div class="col-md-12 text-center">
												<a href="{{ url('posts/' . data_get($post, 'id') . '/edit') }}"
												   class="btn btn-default btn-lg"
												>{{ t('Previous') }}</a>
                                                <a id="nextStepAction"
                                                   href="{{ $nextStepUrl }}"
                                                   class="btn btn-default btn-lg"
                                                   onclick="this.className += ' disabled'; return true;"
                                                >{{ t('Next') }}</a>
                                            </div>
                                        </div>
                                    	
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.page-content -->
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
@endsection

@php
	$postId = data_get($post, 'id');
	
	/* Get Upload Url */
	$uploadUrl = url('posts/' . data_get($post, 'id') . '/photos/');
	$uploadUrl = qsUrl($uploadUrl, request()->only(['package']), null, false);
@endphp

@section('after_scripts')
    <script src="{{ url('assets/plugins/bootstrap-fileinput/js/plugins/sortable.min.js') }}" type="text/javascript"></script>
    <script src="{{ url('assets/plugins/bootstrap-fileinput/js/fileinput.min.js') }}" type="text/javascript"></script>
	<script src="{{ url('assets/plugins/bootstrap-fileinput/themes/' . $fiTheme . '/theme.js') }}" type="text/javascript"></script>
	<script src="{{ url('common/js/fileinput/locales/' . config('app.locale') . '.js') }}" type="text/javascript"></script>
    <script>
		let options = {};
		options.theme = '{{ $fiTheme }}';
		options.language = '{{ config('app.locale') }}';
		options.rtl = {{ (config('lang.direction') == 'rtl') ? 'true' : 'false' }};
		options.overwriteInitial = false;
		options.showCaption = false;
		options.showPreview = true;
		options.allowedFileExtensions = {!! getUploadFileTypes('image', true) !!};
		options.uploadUrl = '{{ $uploadUrl }}';
		options.uploadAsync = false;
		options.showCancel = false;
		options.showUpload = false;
		options.showRemove = false;
		options.showBrowse = true;
		options.browseClass = 'btn btn-primary';
		options.minFileSize = {{ (int)config('settings.upload.min_image_size', 0) }};
		options.maxFileSize = {{ (int)config('settings.upload.max_image_size', 1000) }};
		options.browseOnZoneClick = true;
		options.minFileCount = 0;
		options.maxFileCount = {{ $picturesLimit }};
		options.validateInitialCount = true;
		options.initialPreview = [];
		options.initialPreviewAsData = true;
		options.initialPreviewFileType = 'image';
		options.initialPreviewConfig = [];
		options.fileActionSettings = {
			showRotate: false,
			showUpload: false,
			showDrag: true,
			showRemove: true,
			removeClass: 'btn btn-outline-danger btn-sm',
			showZoom: true,
			zoomClass: 'btn btn-outline-secondary btn-sm',
		};
		options.elErrorContainer = '#uploadError';
		options.msgErrorClass = 'alert alert-block alert-danger';
		
		@if (!empty($pictures))
			@foreach($pictures as $idx => $picture)
				@php
					$pictureId = data_get($picture, 'id');
					$pictureUrl = data_get($picture, 'url.medium');
					$deleteUrl = url('posts/' . $postId . '/photos/' . $pictureId . '/delete');
					$filePath = data_get($picture, 'filename');
					try {
						$fileExists = (isset($disk) && !empty($filePath) && $disk->exists($filePath));
						$fileSize = $fileExists ? (int)$disk->size($filePath) : 0;
					} catch (\Throwable $e) {
						$fileSize = 0;
					}
				@endphp
				options.initialPreview[{{ $idx }}] = '{{ $pictureUrl }}';
				options.initialPreviewConfig[{{ $idx }}] = {};
				options.initialPreviewConfig[{{ $idx }}].key = {{ (int)($pictureId ?? $idx) }};
				options.initialPreviewConfig[{{ $idx }}].caption = '{{ basename($filePath) }}';
				options.initialPreviewConfig[{{ $idx }}].size = {{ $fileSize }};
				options.initialPreviewConfig[{{ $idx }}].url = '{{ $deleteUrl }}';
			@endforeach
		@endif
		
		{{-- fileinput --}}
		let pictureFieldEl = $('#pictureField');
        pictureFieldEl.fileinput(options);
		
		/* Reset the upload status message */
		pictureFieldEl.on('filebatchpreupload', function(event, data) {
			$('#uploadSuccess').html('<ul></ul>').hide();
		});
		
		/* Auto-upload files */
		pictureFieldEl.on('filebatchselected', function(event, files) {
			$(this).fileinput('upload');
		});
		
		/* Show the upload success message */
        pictureFieldEl.on('filebatchuploadsuccess', function(event, data) {
            /* Show uploads success messages */
            let out = '';
            $.each(data.files, function(key, file) {
                if (typeof file !== 'undefined') {
                    let fname = file.name;
                    out = out + {!! t('fileinput_file_uploaded_successfully') !!};
                }
            });
	        let uploadSuccessEl = $('#uploadSuccess');
	        uploadSuccessEl.find('ul').append(out);
	        uploadSuccessEl.fadeIn('slow');
            
            /* Change button label */
            $('#nextStepAction').html('{{ $nextStepLabel }}').removeClass('btn-default').addClass('btn-primary');
        });
		
		/* Show upload error message */
		pictureFieldEl.on('filebatchuploaderror', function(event, data, msg) {
			showErrorMessage(msg);
		});
		
		/* Before deletion */
        pictureFieldEl.on('filepredelete', function(event, key, jqXHR, data) {
            let abort = true;
            if (confirm("{{ t('Are you sure you want to delete this picture') }}")) {
                abort = false;
            }
            
            return abort;
        });
		
		/* Show the deletion success message */
		pictureFieldEl.on('filedeleted', function(event, key, jqXHR, data) {
			/* Check local vars */
			if (typeof jqXHR.responseJSON === 'undefined') {
				return false;
			}
			
			let obj = jqXHR.responseJSON;
			if (typeof obj.status === 'undefined' || typeof obj.message === 'undefined') {
				return false;
			}
			
			/* Deletion Notification */
			if (parseInt(obj.status) === 1) {
				showSuccessMessage(obj.message);
			} else {
				showErrorMessage(obj.message);
			}
		});
		
		/* Show deletion error message */
		pictureFieldEl.on('filedeleteerror', function(event, data, msg) {
			showErrorMessage(msg);
		});
		
		/* Reorder (Sort) files */
		pictureFieldEl.on('filesorted', function(event, params) {
			reorderPictures(params);
		});

		/**
		 * Reorder (Sort) pictures
		 * @param params
		 * @returns {boolean}
		 */
		function reorderPictures(params)
		{
			if (typeof params.stack === 'undefined') {
				return false;
			}
			
			waitingDialog.show('{{ t('Processing') }}...');
			
			let postId = '{{ request()->segment(2) }}';
			
			let ajax = $.ajax({
				method: 'POST',
				url: siteUrl + '/posts/' + postId + '/photos/reorder',
				data: {
					'params': params,
					'_token': $('input[name=_token]').val()
				}
			});
			ajax.done(function(data) {
				
				setTimeout(function() {
					waitingDialog.hide();
				}, 250);
				
				if (typeof data.status === 'undefined') {
					return false;
				}
				
				/* Reorder Notification */
				if (parseInt(data.status) === 1) {
					showSuccessMessage(data.message);
				} else {
					showErrorMessage(data.message);
				}
				
				return false;
			});
			ajax.fail(function (xhr, textStatus, errorThrown) {
				let message = getJqueryAjaxError(xhr);
				if (message !== null) {
					showErrorMessage(message);
				}
			});
			
			return false;
		}
		
		/**
		 * Show Success Message
		 * @param message
		 */
		function showSuccessMessage(message)
		{
			let errorEl = $('#uploadError');
			let successEl = $('#uploadSuccess');
			
			errorEl.hide().empty();
			errorEl.removeClass('alert alert-block alert-danger');
			
			successEl.html('<ul></ul>').hide();
			successEl.find('ul').append(message);
			successEl.fadeIn('fast');
		}
		
		/**
		 * Show Errors Message
		 * @param message
		 */
		function showErrorMessage(message)
		{
			jsAlert(message, 'error', false);
			
			let errorEl = $('#uploadError');
			let successEl = $('#uploadSuccess');
			
			/* Error Notification */
			successEl.empty().hide();
			
			errorEl.html('<ul></ul>').hide();
			errorEl.addClass('alert alert-block alert-danger');
			errorEl.find('ul').append(message);
			errorEl.fadeIn('fast');
		}
    </script>
    
@endsection
