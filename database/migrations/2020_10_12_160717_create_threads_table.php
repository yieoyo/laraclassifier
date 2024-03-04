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
		Schema::create('threads', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->bigInteger('post_id')->unsigned()->nullable();
			$table->string('subject', 200)->nullable();
			$table->bigInteger('deleted_by')->unsigned()->nullable();
			$table->timestamp('deleted_at')->nullable();
			$table->timestamps();
			
			$table->index(['post_id']);
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
		Schema::dropIfExists('threads');
	}
};
