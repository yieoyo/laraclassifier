@foreach (session('flash_notification', collect())->toArray() as $message)
	@if ($message['overlay'])
		@include('flash::modal', [
			'modalClass' => 'flash-modal',
			'title'      => $message['title'],
			'body'       => $message['message']
		])
	@else
		<div class="alert alert-{{ $message['level'] }} {{ $message['important'] ? 'alert-dismissible' : '' }}" role="alert">
			@if ($message['important'])
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
			@endif
			
			{!! $message['message'] !!}
		</div>
	@endif
@endforeach

{{ session()->forget('flash_notification') }}
