<div class="alert alert-info" role="alert">
	@if (request()->query('filter') == 'unread')
		{{ t('No new thread or with new messages') }}
	@elseif (request()->query('filter') == 'started')
		{{ t('No thread started by you') }}
	@elseif (request()->query('filter') == 'important')
		{{ t('No message marked as important') }}
	@else
		{{ t('No message received') }}
	@endif
</div>
