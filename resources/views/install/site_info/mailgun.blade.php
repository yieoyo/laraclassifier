<div class="row row-cols-2 mailgun-box">
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'mailgun_domain',
			'label' => trans('messages.mailgun_domain'),
			'value' => $siteInfo['mailgun_domain'] ?? '',
			'rules' => $mailRules['mailgun'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'mailgun_secret',
			'label' => trans('messages.mailgun_secret'),
			'value' => $siteInfo['mailgun_secret'] ?? '',
			'rules' => $mailRules['mailgun'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'mailgun_endpoint',
			'label' => trans('admin.mail_mailgun_endpoint_label'),
			'value' => $siteInfo['mailgun_endpoint'] ?? 'api.mailgun.net',
			'rules' => $mailRules['mailgun'] ?? [],
		])
	</div>
	
	<div class="col"></div>
	
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'mailgun_host',
			'label' => trans('admin.mail_smtp_host_label'),
			'value' => $siteInfo['mailgun_host'] ?? '',
			'hint'  => trans('admin.mail_smtp_host_hint'),
			'rules' => $mailRules['mailgun'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'mailgun_port',
			'label' => trans('admin.mail_smtp_port_label'),
			'value' => $siteInfo['mailgun_port'] ?? '',
			'hint'  => trans('admin.mail_smtp_port_hint'),
			'rules' => $mailRules['mailgun'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'mailgun_username',
			'label' => trans('admin.mail_smtp_username_label'),
			'value' => $siteInfo['mailgun_username'] ?? '',
			'hint'  => trans('admin.mail_smtp_username_hint'),
			'rules' => $mailRules['mailgun'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'mailgun_password',
			'label' => trans('admin.mail_smtp_password_label'),
			'value' => $siteInfo['mailgun_password'] ?? '',
			'hint'  => trans('admin.mail_smtp_password_hint'),
			'rules' => $mailRules['mailgun'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'mailgun_encryption',
			'label' => trans('admin.mail_smtp_encryption_label'),
			'value' => $siteInfo['mailgun_encryption'] ?? '',
			'hint'  => trans('admin.mail_smtp_encryption_hint'),
			'rules' => $mailRules['mailgun'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'mailgun_email_sender',
			'label' => trans('admin.mail_email_sender_label'),
			'value' => $siteInfo['mailgun_email_sender'] ?? ($siteInfo['email'] ?? ''),
			'hint'  => trans('admin.mail_email_sender_hint'),
			'rules' => $mailRules['mailgun'] ?? [],
		])
	</div>
</div>