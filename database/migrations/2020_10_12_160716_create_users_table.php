<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up(): void
	{
		Schema::create('users', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->string('country_code', 2)->nullable();
			$table->string('language_code', 10)->nullable();
			$table->tinyInteger('user_type_id')->unsigned()->nullable();
			$table->integer('gender_id')->unsigned()->nullable();
			$table->string('name', 100);
			$table->string('photo', 255)->nullable();
			$table->string('about', 255)->nullable();
			$table->enum('auth_field', ['email', 'phone'])->nullable()->default('email');
			$table->string('email', 100)->nullable();
			$table->string('phone', 60)->nullable();
			$table->string('phone_national', 30)->nullable();
			$table->string('phone_country', 2)->nullable();
			$table->boolean('phone_hidden')->nullable()->default('0');
			$table->string('username', 100)->nullable();
			$table->string('password', 60)->nullable();
			$table->string('remember_token', 100)->nullable();
			$table->boolean('is_admin')->nullable()->default('0');
			$table->boolean('can_be_impersonated')->nullable()->default('1');
			$table->boolean('disable_comments')->nullable()->default('0');
			$table->string('create_from_ip', 50)->nullable()->comment('IP address of creation');
			$table->string('latest_update_ip', 50)->nullable()->comment('Latest update IP address');
			$table->string('provider', 50)->nullable()->comment('facebook, google, twitter, linkedin, ...');
			$table->string('provider_id', 50)->nullable()->comment('Provider User ID');
			$table->string('email_token', 32)->nullable();
			$table->string('phone_token', 32)->nullable();
			$table->timestamp('email_verified_at')->nullable()->useCurrentOnUpdate();
			$table->timestamp('phone_verified_at')->nullable()->useCurrentOnUpdate();
			$table->boolean('accept_terms')->nullable()->default('0');
			$table->boolean('accept_marketing_offers')->nullable()->default('0');
			$table->string('time_zone', 50)->nullable();
			$table->boolean('featured')->nullable()->default('0')
				->comment('Need to be cleared form a cron tab command');
			$table->boolean('blocked')->nullable()->default('0');
			$table->boolean('closed')->nullable()->default('0');
			$table->datetime('last_activity')->nullable();
			$table->datetime('last_login_at')->nullable();
			$table->timestamp('deleted_at')->nullable();
			$table->timestamps();
			
			$table->index(['country_code']);
			$table->index(['user_type_id']);
			$table->index(['gender_id']);
			$table->index(['auth_field']);
			$table->index(['email']);
			$table->index(['phone']);
			$table->index(['phone_country']);
			$table->index(['username']);
			$table->index(['email_verified_at']);
			$table->index(['phone_verified_at']);
			$table->index(['is_admin']);
			$table->index(['can_be_impersonated']);
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::dropIfExists('users');
	}
};
