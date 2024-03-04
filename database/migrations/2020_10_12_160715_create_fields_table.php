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
		Schema::create('fields', function (Blueprint $table) {
			$table->increments('id');
			$table->enum('belongs_to', ['post', 'user'])->default('post');
			$table->text('name')->nullable();
			$table->string('type', 50)->default('text');
			$table->integer('max')->unsigned()->nullable()->default('255');
			$table->text('default_value')->nullable();
			$table->boolean('required')->nullable();
			$table->boolean('use_as_filter')->nullable()->default('0');
			$table->text('help')->nullable();
			$table->boolean('active')->nullable();
			
			$table->index(['belongs_to']);
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
		Schema::dropIfExists('fields');
	}
};
