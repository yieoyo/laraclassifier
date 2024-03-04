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
		Schema::create('subadmin1', function (Blueprint $table) {
			$table->increments('id');
			$table->string('code', 100);
			$table->string('country_code', 2)->nullable();
			$table->text('name');
			$table->boolean('active')->nullable()->default('1');
			
			$table->unique(['code']);
			$table->index(['country_code']);
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
		Schema::dropIfExists('subadmin1');
	}
};
