<div class="row row-cols-2 smtp-box">
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'smtp_host',
			'label' => trans('messages.hostname'),
			'value' => $siteInfo['smtp_host'] ?? '',
			'hint'  => trans('admin.mail_smtp_host_hint'),
			'rules' => $mailRules['smtp'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'smtp_port',
			'label' => trans('messages.port'),
			'value' => $siteInfo['smtp_port'] ?? '',
			'hint'  => trans('admin.mail_smtp_port_hint'),
			'rules' => $mailRules['smtp'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'smtp_username',
			'label' => trans('messages.username'),
			'value' => $siteInfo['smtp_username'] ?? '',
			'hint'  => trans('admin.mail_smtp_username_hint'),
			'rules' => $mailRules['smtp'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'smtp_password',
			'label' => trans('messages.password'),
			'value' => $siteInfo['smtp_password'] ?? '',
			'hint'  => trans('admin.mail_smtp_password_hint'),
			'rules' => $mailRules['smtp'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'smtp_encryption',
			'label' => trans('messages.encryption'),
			'value' => $siteInfo['smtp_encryption'] ?? '',
			'hint'  => trans('admin.mail_smtp_encryption_hint'),
			'rules' => $mailRules['smtp'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'smtp_email_sender',
			'label' => trans('admin.mail_email_sender_label'),
			'value' => $siteInfo['smtp_email_sender'] ?? ($siteInfo['email'] ?? ''),
			'hint'  => trans('admin.mail_email_sender_hint'),
			'rules' => $mailRules['smtp'] ?? [],
		])
	</div>
</div>