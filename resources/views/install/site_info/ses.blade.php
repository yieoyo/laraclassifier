<div class="row row-cols-2 ses-box">
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'ses_key',
			'label' => trans('messages.ses_key'),
			'value' => $siteInfo['ses_key'] ?? '',
			'rules' => $mailRules['ses'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'ses_secret',
			'label' => trans('messages.ses_secret'),
			'value' => $siteInfo['ses_secret'] ?? '',
			'rules' => $mailRules['ses'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'ses_region',
			'label' => trans('messages.ses_region'),
			'value' => $siteInfo['ses_region'] ?? '',
			'rules' => $mailRules['ses'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'ses_token',
			'label' => trans('admin.mail_ses_token_label'),
			'value' => $siteInfo['ses_token'] ?? '',
			'hint'  => trans('admin.mail_ses_token_hint'),
			'rules' => $mailRules['ses'] ?? [],
		])
	</div>
	
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'ses_host',
			'label' => trans('admin.mail_smtp_host_label'),
			'value' => $siteInfo['ses_host'] ?? '',
			'hint'  => trans('admin.mail_smtp_host_hint'),
			'rules' => $mailRules['ses'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'ses_port',
			'label' => trans('admin.mail_smtp_port_label'),
			'value' => $siteInfo['ses_port'] ?? '',
			'hint'  => trans('admin.mail_smtp_port_hint'),
			'rules' => $mailRules['ses'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'ses_username',
			'label' => trans('admin.mail_smtp_username_label'),
			'value' => $siteInfo['ses_username'] ?? '',
			'hint'  => trans('admin.mail_smtp_username_hint'),
			'rules' => $mailRules['ses'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'ses_password',
			'label' => trans('admin.mail_smtp_password_label'),
			'value' => $siteInfo['ses_password'] ?? '',
			'hint'  => trans('admin.mail_smtp_password_hint'),
			'rules' => $mailRules['ses'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'ses_encryption',
			'label' => trans('admin.mail_smtp_encryption_label'),
			'value' => $siteInfo['ses_encryption'] ?? '',
			'hint'  => trans('admin.mail_smtp_encryption_hint'),
			'rules' => $mailRules['ses'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'ses_email_sender',
			'label' => trans('admin.mail_email_sender_label'),
			'value' => $siteInfo['ses_email_sender'] ?? ($siteInfo['email'] ?? ''),
			'hint'  => trans('admin.mail_email_sender_hint'),
			'rules' => $mailRules['ses'] ?? [],
		])
	</div>
</div>