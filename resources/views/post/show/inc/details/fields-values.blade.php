@php
	$customFields ??= [];
@endphp
@if (!empty($customFields))
	<div class="row gx-1 gy-1 mt-3">
		<div class="col-12">
			<div class="row mb-3">
				<div class="col-12">
					<h4 class="p-0"><i class="fas fa-bars"></i> {{ t('Additional Details') }}</h4>
				</div>
			</div>
		</div>
		
		<div class="col-12">
			<div class="row gx-1 gy-1">
				@foreach($customFields as $field)
					@php
						$fieldType = data_get($field, 'type');
						$fieldName = data_get($field, 'name');
						$fieldValue = data_get($field, 'value');
					@endphp
					@if (is_array($fieldValue))
						@if (count($fieldValue) > 0)
							<div class="col-12">
								<div class="row bg-light rounded py-2 mx-0">
									<div class="col-12 mb-2 fw-bolder">{{ $fieldName }}:</div>
									<div class="row">
										@foreach($fieldValue as $valueItem)
											<div class="col-sm-4 col-6 py-2">
												<i class="fa fa-check"></i> {{ $valueItem }}
											</div>
										@endforeach
									</div>
								</div>
							</div>
						@endif
					@else
						@if (is_string($fieldValue) || is_numeric($fieldValue) || is_bool($fieldValue))
							@if ($fieldType == 'file')
								<div class="col-12">
									<div class="row bg-light rounded py-2 mx-0">
										<div class="col-6 fw-bolder">{{ $fieldName }}</div>
										<div class="col-6 text-sm-end text-start">
											<a class="btn btn-default" href="{{ $fieldValue }}" target="_blank">
												<i class="fas fa-paperclip"></i> {{ t('Download') }}
											</a>
										</div>
									</div>
								</div>
							@elseif ($fieldType == 'video')
								<div class="col-12">
									<div class="row bg-light rounded py-2 mx-0">
										<div class="col-12 fw-bolder">{{ $fieldName }}:</div>
										<div class="col-12 text-center embed-responsive embed-responsive-16by9">
											{!! $fieldValue !!}
										</div>
									</div>
								</div>
							@else
								<div class="col-sm-6 col-12">
									<div class="row bg-light rounded py-2 mx-0">
										<div class="col-6 fw-bolder">{{ $fieldName }}</div>
										<div class="col-6 text-sm-end text-start">
											@if ($fieldType == 'url')
												<a href="{{ $fieldValue }}" target="_blank" rel="nofollow">{{ $fieldValue }}</a>
											@else
												{{ $fieldValue }}
											@endif
										</div>
									</div>
								</div>
							@endif
						@endif
					@endif
				@endforeach
			</div>
		</div>
	</div>
@endif
