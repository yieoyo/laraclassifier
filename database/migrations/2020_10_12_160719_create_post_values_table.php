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
		Schema::create('post_values', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->bigInteger('post_id')->unsigned()->nullable();
			$table->integer('field_id')->unsigned()->nullable();
			$table->integer('option_id')->unsigned()->nullable();
			$table->mediumtext('value')->nullable();
			
			$table->index(['post_id']);
			$table->index(['field_id']);
			$table->index(['option_id']);
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::dropIfExists('post_values');
	}
};
