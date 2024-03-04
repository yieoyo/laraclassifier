<div class="row row-cols-2 postmark-box">
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'postmark_token',
			'label' => trans('messages.postmark_token'),
			'value' => $siteInfo['postmark_token'] ?? '',
			'rules' => $mailRules['postmark'] ?? [],
		])
	</div>
	
	<div class="col"></div>
	
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'postmark_host',
			'label' => trans('admin.mail_smtp_host_label'),
			'value' => $siteInfo['postmark_host'] ?? '',
			'hint'  => trans('admin.mail_smtp_host_hint'),
			'rules' => $mailRules['postmark'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'postmark_port',
			'label' => trans('admin.mail_smtp_port_label'),
			'value' => $siteInfo['postmark_port'] ?? '',
			'hint'  => trans('admin.mail_smtp_port_hint'),
			'rules' => $mailRules['postmark'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'postmark_username',
			'label' => trans('admin.mail_smtp_username_label'),
			'value' => $siteInfo['postmark_username'] ?? '',
			'hint'  => trans('admin.mail_smtp_username_hint'),
			'rules' => $mailRules['postmark'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'postmark_password',
			'label' => trans('admin.mail_smtp_password_label'),
			'value' => $siteInfo['postmark_password'] ?? '',
			'hint'  => trans('admin.mail_smtp_password_hint'),
			'rules' => $mailRules['postmark'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'postmark_encryption',
			'label' => trans('admin.mail_smtp_encryption_label'),
			'value' => $siteInfo['postmark_encryption'] ?? '',
			'hint'  => trans('admin.mail_smtp_encryption_hint'),
			'rules' => $mailRules['postmark'] ?? [],
		])
	</div>
	<div class="col">
		@include('install.helpers.form_control', [
			'type'  => 'text',
			'name'  => 'postmark_email_sender',
			'label' => trans('admin.mail_email_sender_label'),
			'value' => $siteInfo['postmark_email_sender'] ?? ($siteInfo['email'] ?? ''),
			'hint'  => trans('admin.mail_email_sender_hint'),
			'rules' => $mailRules['postmark'] ?? [],
		])
	</div>
</div>