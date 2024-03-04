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
		Schema::create('payments', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->bigInteger('payable_id')->unsigned()->nullable()->comment('Post|User ID');
			$table->string('payable_type', 255)->nullable()->comment('Post|User class name');
			$table->integer('package_id')->unsigned()->nullable();
			$table->integer('payment_method_id')->unsigned()->nullable();
			$table->string('transaction_id', 255)->nullable()->comment('Transaction\'s ID from the Provider');
			$table->decimal('amount', 10, 2)->unsigned()->default('0.00');
			$table->string('currency_code', 3)->nullable();
			$table->timestamp('period_start');
			$table->timestamp('period_end');
			$table->timestamp('canceled_at')->nullable()->comment('Canceled by the user before the period end');
			$table->timestamp('refunded_at')->nullable();
			$table->boolean('active')->nullable()->default('1');
			$table->timestamps();
			
			$table->index(['payable_id', 'payable_type']);
			$table->index(['package_id']);
			$table->index(['payment_method_id']);
			$table->index(['transaction_id']);
			$table->index(['period_start', 'period_end']);
			$table->index(['canceled_at']);
			$table->index(['refunded_at']);
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
		Schema::dropIfExists('payments');
	}
};
