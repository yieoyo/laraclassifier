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
		Schema::create('blacklist', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->enum('type', ['domain', 'email', 'phone', 'ip', 'word'])->nullable();
			$table->string('entry', 100)->default('');
			
			$table->index(['type', 'entry']);
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::dropIfExists('blacklist');
	}
};
