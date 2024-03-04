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
		Schema::create('pictures', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->bigInteger('post_id')->unsigned()->nullable();
			$table->string('filename', 255)->nullable();
			$table->string('mime_type', 200)->nullable();
			$table->integer('position')->unsigned()->default('0');
			$table->boolean('active')->nullable()->default('1');
			$table->timestamps();
			
			$table->index(['post_id']);
			$table->index(['position']);
			$table->index(['active']);
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
		Schema::dropIfExists('pictures');
	}
};
