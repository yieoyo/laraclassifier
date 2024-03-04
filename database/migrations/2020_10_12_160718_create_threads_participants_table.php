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
		Schema::create('threads_participants', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->bigInteger('thread_id')->unsigned()->nullable();
			$table->bigInteger('user_id')->unsigned()->nullable();
			$table->timestamp('last_read')->nullable();
			$table->boolean('is_important')->nullable()->default('0');
			$table->timestamp('deleted_at')->nullable();
			$table->timestamps();
			
			$table->index(['thread_id']);
			$table->index(['user_id']);
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::dropIfExists('threads_participants');
	}
};
