@php
	$thread ??= [];
	$message ??= [];
@endphp
@if (auth()->id() == data_get($message, 'user.id'))
	<div class="chat-item object-me">
		<div class="chat-item-content">
			<div class="msg">
				{!! urls_to_links(nlToBr(data_get($message, 'body')), ['class' => 'text-light']) !!}
				@if (!empty(data_get($message, 'filename')) && $disk->exists(data_get($message, 'filename')))
					@php
						$mt2Class = !empty(trim(data_get($message, 'body'))) ? 'mt-2' : '';
					@endphp
					<div class="{{ $mt2Class }}">
						<i class="fas fa-paperclip" aria-hidden="true"></i>
						<a class="text-light"
						   href="{{ privateFileUrl(data_get($message, 'filename'), null) }}"
						   target="_blank"
						   data-bs-toggle="tooltip"
						   data-bs-placement="left"
						   title="{{ basename(data_get($message, 'filename')) }}"
						>
							{{ str(data_get($message, 'filename'))->basename()->limit(20) }}
						</a>
					</div>
				@endif
			</div>
			<span class="time-and-date">
				{{ data_get($message, 'created_at_formatted') }}
				@php
					$recipient = data_get($message, 'p_recipient');
					
					$threadUpdatedAt = new \Illuminate\Support\Carbon(data_get($thread, 'updated_at'));
					$threadUpdatedAt->timezone(\App\Helpers\Date::getAppTimeZone());
					
					$recipientLastRead = new \Illuminate\Support\Carbon(data_get($recipient, 'last_read'));
					$recipientLastRead->timezone(\App\Helpers\Date::getAppTimeZone());
					
					$threadIsUnreadByThisRecipient = (
						!empty($recipient)
						&& (
							data_get($recipient, 'last_read') === null
							|| $threadUpdatedAt->gt($recipientLastRead)
						)
					);
				@endphp
				@if ($threadIsUnreadByThisRecipient)
					&nbsp;<i class="fas fa-check-double"></i>
				@endif
			</span>
		</div>
	</div>
@else
	<div class="chat-item object-user">
		<div class="object-user-img">
			<a href="{{ \App\Helpers\UrlGen::user(data_get($message, 'user')) }}">
				<img src="{{ url(data_get($message, 'user.photo_url')) }}" alt="{{ data_get($message, 'user.name') }}">
			</a>
		</div>
		<div class="chat-item-content">
			<div class="chat-item-content-inner">
				<div class="msg bg-white">
					{!! urls_to_links(nlToBr(data_get($message, 'body'))) !!}
					@if (!empty(data_get($message, 'filename')) && $disk->exists(data_get($message, 'filename')))
						@php
							$mt2Class = !empty(trim(data_get($message, 'body'))) ? 'mt-2' : '';
						@endphp
						<div class="{{ $mt2Class }}">
							<i class="fas fa-paperclip" aria-hidden="true"></i>
							<a class=""
							   href="{{ privateFileUrl(data_get($message, 'filename'), null) }}"
							   target="_blank"
							   data-bs-toggle="tooltip"
							   data-bs-placement="left"
							   title="{{ basename(data_get($message, 'filename')) }}"
							>
								{{ str(data_get($message, 'filename'))->basename()->limit(20) }}
							</a>
						</div>
					@endif
				</div>
				@php
					$userIsOnline = isUserOnline(data_get($message, 'user'));
				@endphp
				<span class="time-and-date ms-0">
					@if ($userIsOnline)
						<i class="fa fa-circle color-success"></i>&nbsp;
					@endif
					{{ data_get($message, 'created_at_formatted') }}
				</span>
			</div>
		</div>
	</div>
@endif
