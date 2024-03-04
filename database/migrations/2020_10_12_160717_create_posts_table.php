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
		Schema::create('posts', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->string('country_code', 2)->nullable();
			$table->bigInteger('user_id')->unsigned()->nullable();
			$table->bigInteger('payment_id')->unsigned()->nullable()
				->comment('ID of the subscription used to publish the listing');
			$table->integer('category_id')->unsigned()->nullable();
			$table->integer('post_type_id')->unsigned()->nullable();
			$table->string('title', 191);
			$table->text('description');
			$table->text('tags')->nullable();
			$table->decimal('price', 17, 2)->unsigned()->nullable();
			$table->string('currency_code', 3)->nullable();
			$table->boolean('negotiable')->nullable()->default('0');
			$table->string('contact_name', 191)->nullable();
			$table->enum('auth_field', ['email', 'phone'])->nullable()->default('email');
			$table->string('email', 100)->nullable();
			$table->string('phone', 60)->nullable();
			$table->string('phone_national', 30)->nullable();
			$table->string('phone_country', 2)->nullable();
			$table->boolean('phone_hidden')->nullable()->default('0');
			$table->string('address', 191)->nullable();
			$table->bigInteger('city_id')->unsigned()->nullable();
			$table->float('lon')->nullable()->comment('longitude in decimal degrees (wgs84)');
			$table->float('lat')->nullable()->comment('latitude in decimal degrees (wgs84)');
			$table->string('create_from_ip', 50)->nullable()->comment('IP address of creation');
			$table->string('latest_update_ip', 50)->nullable()->comment('Latest update IP address');
			$table->integer('visits')->unsigned()->nullable()->default('0');
			$table->string('email_token', 32)->nullable();
			$table->string('phone_token', 32)->nullable();
			$table->string('tmp_token', 32)->nullable();
			$table->timestamp('email_verified_at')->nullable();
			$table->timestamp('phone_verified_at')->nullable()->useCurrentOnUpdate();
			$table->timestamp('reviewed_at')->nullable();
			$table->boolean('accept_terms')->nullable()->default('0');
			$table->boolean('accept_marketing_offers')->nullable()->default('0');
			$table->boolean('is_permanent')->nullable()->default('0');
			$table->boolean('featured')->nullable()->default('0');
			$table->timestamp('archived_at')->nullable();
			$table->timestamp('archived_manually_at')->nullable();
			$table->timestamp('deletion_mail_sent_at')->nullable();
			$table->string('fb_profile', 255)->nullable();
			$table->string('partner', 50)->nullable();
			$table->timestamp('deleted_at')->nullable();
			$table->timestamps();
			
			$table->index(['lon', 'lat']);
			$table->index(['country_code']);
			$table->index(['user_id']);
			$table->index(['category_id']);
			$table->index(['title']);
			$table->index(['address']);
			$table->index(['city_id']);
			$table->index(['featured']);
			$table->index(['post_type_id']);
			$table->index(['contact_name']);
			$table->index(['auth_field']);
			$table->index(['email']);
			$table->index(['phone']);
			$table->index(['phone_country']);
			$table->index(['email_verified_at']);
			$table->index(['phone_verified_at']);
			$table->index(['reviewed_at']);
			$table->index(['archived_at']);
			$table->index(['is_permanent']);
			$table->index(['created_at']);
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::dropIfExists('posts');
	}
};
