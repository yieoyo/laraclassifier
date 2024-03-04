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
		Schema::create('continents', function (Blueprint $table) {
			$table->increments('id');
			$table->string('code', 2);
			$table->string('name', 100);
			$table->boolean('active')->nullable()->default('1');
			
			$table->unique(['code']);
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
		Schema::dropIfExists('continents');
	}
};
