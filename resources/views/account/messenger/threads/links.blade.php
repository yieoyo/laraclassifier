@php
	$apiResult ??= [];
	$from = (int)data_get($apiResult, 'meta.from', 0);
	$to = (int)data_get($apiResult, 'meta.to', 0);
	$totalEntries = (int)data_get($apiResult, 'meta.total', 0);
@endphp
@if ($totalEntries > 0)
	<span class="text-muted count-message">
		<strong>
			{{ $from }}
		</strong> - <strong>
			{{ $to }}
		</strong> {{ t('of') }} <strong>
			{{ $totalEntries }}
		</strong>
	</span>
	@include('account.messenger.threads.pagination')
@endif
