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
		Schema::create('packages', function (Blueprint $table) {
			$table->increments('id');
			$table->enum('type', ['promotion', 'subscription'])->default('promotion')
				->comment('Post promotion OR User subscription');
			$table->text('name')->nullable()->comment('In country language');
			$table->text('short_name')->nullable()->comment('In country language');
			$table->enum('ribbon', ['red', 'orange', 'green'])->nullable();
			$table->boolean('has_badge')->nullable()->default('0');
			$table->decimal('price', 10, 2)->unsigned()->nullable();
			$table->string('currency_code', 3)->nullable();
			$table->integer('promotion_time')->nullable()->comment('In days');
			$table->enum('interval', ['week', 'month', 'year'])->nullable()
				->comment('Package\'s validity period');
			$table->integer('listings_limit')->nullable()
				->comment('Listings per subscriber (during the "interval")');
			$table->integer('pictures_limit')->nullable()->default('5')
				->comment('Pictures per listing (for post & user\'s post)');
			$table->integer('expiration_time')->nullable()->unsigned()->default('30')
				->comment('Listing expiration time (In days)');
			$table->text('description')->nullable()->comment('In country language');
			$table->integer('facebook_ads_duration')->unsigned()->nullable()->default('0');
			$table->integer('google_ads_duration')->unsigned()->nullable()->default('0');
			$table->integer('twitter_ads_duration')->unsigned()->nullable()->default('0');
			$table->integer('linkedin_ads_duration')->unsigned()->nullable()->default('0');
			$table->boolean('recommended')->nullable()->default('0');
			$table->integer('parent_id')->unsigned()->nullable();
			$table->integer('lft')->unsigned()->nullable();
			$table->integer('rgt')->unsigned()->nullable();
			$table->integer('depth')->unsigned()->nullable();
			$table->boolean('active')->nullable()->default('0');
			
			$table->index(['type']);
			$table->index(['lft']);
			$table->index(['rgt']);
			$table->index(['active']);
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::dropIfExists('packages');
	}
};
