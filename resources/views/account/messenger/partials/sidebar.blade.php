@php
	$stats ??= [];
	$countThreadsWithNewMessage = (int)data_get($stats, 'threads.withNewMessage');
@endphp
<div class="col-md-3 col-lg-2">
	<ul class="nav nav-pills inbox-nav">
		<li class="nav-item{{ (!request()->has('filter') || request()->query('filter')=='') ? ' active' : '' }}">
			<a class="nav-link" href="{{ url('account/messages') }}">
				{{ t('inbox') }}
				@php
					$badgeColor = (!request()->has('filter') || request()->query('filter')=='') ? 'bg-light' : 'bg-primary text-white';
					$visibility = ($countThreadsWithNewMessage <= 0) ? ' hide' : '';
				@endphp
				<span class="count-threads-with-new-messages count badge {{ $badgeColor }}{{ $visibility }}">
					{{ \App\Helpers\Number::short($countThreadsWithNewMessage) }}
				</span>
			</a>
		</li>
		<li class="nav-item{{ (request()->query('filter')=='unread') ? ' active' : '' }}">
			<a class="nav-link" href="{{ url('account/messages?filter=unread') }}">
				{{ t('unread') }}
			</a>
		</li>
		<li class="nav-item{{ (request()->query('filter')=='started') ? ' active' : '' }}">
			<a class="nav-link" href="{{ url('account/messages?filter=started') }}">
				{{ t('started') }}
			</a>
		</li>
		<li class="nav-item{{ (request()->query('filter')=='important') ? ' active' : '' }}">
			<a class="nav-link" href="{{ url('account/messages?filter=important') }}">
				{{ t('important') }}
			</a>
		</li>
	</ul>
</div>
