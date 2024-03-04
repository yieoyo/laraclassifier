<div class="row row-cols-2 sparkpost-box">
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'sparkpost_secret',
			'label' => trans('messages.sparkpost_secret'),
			'value' => $siteInfo['sparkpost_secret'] ?? '',
			'rules' => $mailRules['sparkpost'] ?? [],
		])
	</div>
	
	<div class="col"></div>
	
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'sparkpost_host',
			'label' => trans('admin.mail_smtp_host_label'),
			'value' => $siteInfo['sparkpost_host'] ?? '',
			'hint'  => trans('admin.mail_smtp_host_hint'),
			'rules' => $mailRules['sparkpost'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'sparkpost_port',
			'label' => trans('admin.mail_smtp_port_label'),
			'value' => $siteInfo['sparkpost_port'] ?? '',
			'hint'  => trans('admin.mail_smtp_port_hint'),
			'rules' => $mailRules['sparkpost'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'sparkpost_username',
			'label' => trans('admin.mail_smtp_username_label'),
			'value' => $siteInfo['sparkpost_username'] ?? '',
			'hint'  => trans('admin.mail_smtp_username_hint'),
			'rules' => $mailRules['sparkpost'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'sparkpost_password',
			'label' => trans('admin.mail_smtp_password_label'),
			'value' => $siteInfo['sparkpost_password'] ?? '',
			'hint'  => trans('admin.mail_smtp_password_hint'),
			'rules' => $mailRules['sparkpost'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'sparkpost_encryption',
			'label' => trans('admin.mail_smtp_encryption_label'),
			'value' => $siteInfo['sparkpost_encryption'] ?? '',
			'hint'  => trans('admin.mail_smtp_encryption_hint'),
			'rules' => $mailRules['sparkpost'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'sparkpost_email_sender',
			'label' => trans('admin.mail_email_sender_label'),
			'value' => $siteInfo['sparkpost_email_sender'] ?? ($siteInfo['email'] ?? ''),
			'hint'  => trans('admin.mail_email_sender_hint'),
			'rules' => $mailRules['sparkpost'] ?? [],
		])
	</div>
</div>