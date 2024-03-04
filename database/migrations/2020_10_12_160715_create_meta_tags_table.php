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
		Schema::create('meta_tags', function (Blueprint $table) {
			$table->increments('id');
			$table->string('page', 50)->nullable();
			$table->text('title')->nullable();
			$table->text('description')->nullable();
			$table->text('keywords')->nullable();
			$table->boolean('active')->nullable()->default('1');
			
			$table->index(['page']);
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
		Schema::dropIfExists('meta_tags');
	}
};
