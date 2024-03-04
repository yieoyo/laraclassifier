<div class="row row-cols-2 sendmail-box">
	<div class="col">
		@php
			$sendmailPath = '/usr/sbin/sendmail -bs';
		@endphp
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'sendmail_path',
			'label' => trans('messages.sendmail_path'),
			'value' => $siteInfo['sendmail_path'] ?? $sendmailPath,
			'hint'  => trans('admin.sendmail_path_hint'),
			'rules' => $mailRules['sendmail'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'sendmail_email_sender',
			'label' => trans('admin.mail_email_sender_label'),
			'value' => $siteInfo['sendmail_email_sender'] ?? ($siteInfo['email'] ?? ''),
			'hint'  => trans('admin.mail_email_sender_hint'),
			'rules' => $mailRules['sendmail'] ?? [],
		])
	</div>
</div>