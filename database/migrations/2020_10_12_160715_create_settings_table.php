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
		Schema::create('settings', function (Blueprint $table) {
			$table->increments('id');
			$table->string('key', 100);
			$table->string('name', 255);
			$table->mediumtext('value')->nullable();
			$table->string('description', 500)->nullable();
			$table->mediumtext('field')->nullable();
			$table->integer('parent_id')->unsigned()->nullable();
			$table->integer('lft')->unsigned()->nullable();
			$table->integer('rgt')->unsigned()->nullable();
			$table->integer('depth')->unsigned()->nullable();
			$table->boolean('active')->nullable();
			$table->timestamps();
			
			$table->unique(['key']);
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
		Schema::dropIfExists('settings');
	}
};
