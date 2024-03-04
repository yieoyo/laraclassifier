<?php

use App\Helpers\DBTool;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

// ===| FILES |===
try {
	
	File::delete(app_path('Helpers/Functions/upgrade.php'));
	
	File::delete(app_path('Http/Controllers/Api/Category/CategoryBySlug.php'));
	File::delete(app_path('Http/Controllers/Api/Page/PageBySlug.php'));
	
	File::delete(app_path('Http/Controllers/Api/Payment/MultiStepsPaymentTrait.php'));
	File::delete(app_path('Http/Controllers/Api/Payment/SingleStepPaymentTrait.php'));
	
	File::delete(app_path('Http/Controllers/Api/Picture/MultiStepsPicturesTrait.php'));
	File::delete(app_path('Http/Controllers/Api/Picture/SingleStepPicturesTrait.php'));
	
	File::delete(app_path('Http/Controllers/Api/Post/SearchTrait.php'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Post/CreateOrEdit/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Post/Search/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Post/Traits/'));
	
	File::delete(app_path('Http/Controllers/Web/Admin/Panel/Library/Traits/Models/HasEnumFields.php'));
	File::delete(app_path('Http/Controllers/Web/Public/PageController.php'));
	
	File::delete(app_path('Http/Requests/AuthRequest.php'));
	File::delete(app_path('Http/Requests/AvatarRequest.php'));
	File::delete(app_path('Http/Requests/ContactRequest.php'));
	File::delete(app_path('Http/Requests/CustomFieldRequest.php'));
	File::delete(app_path('Http/Requests/ForgotPasswordRequest.php'));
	File::delete(app_path('Http/Requests/LoginRequest.php'));
	File::delete(app_path('Http/Requests/PackageRequest.php'));
	File::delete(app_path('Http/Requests/PhotoRequest.php'));
	File::delete(app_path('Http/Requests/PostRequest.php'));
	File::delete(app_path('Http/Requests/ReplyMessageRequest.php'));
	File::delete(app_path('Http/Requests/ReportRequest.php'));
	File::delete(app_path('Http/Requests/ResetPasswordRequest.php'));
	File::delete(app_path('Http/Requests/SendMessageRequest.php'));
	File::delete(app_path('Http/Requests/UserRequest.php'));
	
	File::delete(app_path('Macros/extractZip.php'));
	File::delete(app_path('Macros/jsonExtract.php'));
	File::delete(app_path('Macros/orderBy.php'));
	File::delete(app_path('Macros/transOrWhere.php'));
	File::delete(app_path('Macros/transWhere.php'));
	
	File::delete(app_path('Models/Traits/ActiveTrait.php'));
	File::delete(app_path('Models/Traits/ColumnTrait.php'));
	File::delete(app_path('Models/Traits/VerifiedTrait.php'));
	
	File::delete(base_path('extras/plugins/stripe/traits/StripePaymentIntent.php'));
	File::delete(base_path('extras/plugins/stripe/traits/StripeCheckout.php'));
	File::delete(base_path('extras/plugins/twocheckout/traits/TwocheckoutRedirect.php'));
	File::delete(base_path('extras/plugins/twocheckout/traits/TwocheckoutAuthorize.php'));
	
	File::delete(public_path('assets/plugins/counter-up/jquery.counterup.js'));
	File::delete(public_path('assets/plugins/counter-up/jquery.counterup.min.js'));
	File::delete(public_path('assets/plugins/counter-up/waypoints.min.js'));
	
	File::delete(public_path('assets/plugins/bootstrap-fileinput/js/locales/uz-cyrl.js'));
	File::deleteDirectory(public_path('assets/plugins/bootstrap-fileinput/themes/explorer-fa/'));
	File::deleteDirectory(public_path('assets/plugins/bootstrap-fileinput/themes/explorer-fas/'));
	File::deleteDirectory(public_path('assets/plugins/bootstrap-fileinput/themes/fa/'));
	File::deleteDirectory(public_path('assets/plugins/bootstrap-fileinput/themes/fas/'));
	
	File::deleteDirectory(resource_path('views/pages/inc/'));
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	include_once __DIR__ . '/../../../app/Helpers/Functions/migration.php';
	
	// packages
	if (!Schema::hasColumn('packages', 'type')) {
		Schema::table('packages', function (Blueprint $table) {
			$table->enum('type', ['promotion', 'subscription'])
				->default('promotion')->after('id');
		});
	}
	if (Schema::hasColumn('packages', 'type')) {
		Schema::table('packages', function (Blueprint $table) {
			$tableName = DBTool::table('packages');
			
			// Create indexes
			$indexes = ['type'];
			foreach ($indexes as $index) {
				$indexName = $tableName . '_' . $index . '_index';
				$sql = 'SHOW KEYS FROM ' . $tableName . ' WHERE Key_name="' . $indexName . '"';
				$keyExists = DB::select($sql);
				if (!$keyExists) {
					$table->index([$index], $indexName);
				}
			}
		});
	}
	if (
		Schema::hasColumn('packages', 'promo_duration')
		&& !Schema::hasColumn('packages', 'promotion_time')
	) {
		Schema::table('packages', function ($table) {
			$table->renameColumn('promo_duration', 'promotion_time');
		});
	}
	if (
		!Schema::hasColumn('packages', 'interval')
		&& Schema::hasColumn('packages', 'promotion_time')
	) {
		Schema::table('packages', function (Blueprint $table) {
			$table->enum('interval', ['week', 'month', 'year'])->nullable()
				->comment('Package\'s validity period')
				->after('promotion_time');
		});
	}
	if (
		!Schema::hasColumn('packages', 'listings_limit')
		&& Schema::hasColumn('packages', 'interval')
	) {
		Schema::table('packages', function (Blueprint $table) {
			$table->integer('listings_limit')->nullable()
				->comment('Listings per subscriber (during the "interval")')
				->after('interval');
		});
	}
	if (Schema::hasColumn('packages', 'duration')) {
		Schema::table('packages', function ($table) {
			$table->renameColumn('duration', 'expiration_time');
		});
	}
	
	//...
	
	// payments
	if (
		Schema::hasColumn('payments', 'post_id')
		&& !Schema::hasColumn('payments', 'payable_id')
	) {
		Schema::table('payments', function ($table) {
			$table->renameColumn('post_id', 'payable_id');
		});
	}
	if (
		!Schema::hasColumn('payments', 'payable_type')
		&& Schema::hasColumn('payments', 'payable_id')
	) {
		Schema::table('payments', function (Blueprint $table) {
			$table->string('payable_type', 255)->nullable()
				->comment('Post|User class name')
				->after('payable_id');
		});
	}
	if (!Schema::hasColumn('payments', 'currency_code')) {
		Schema::table('payments', function (Blueprint $table) {
			$table->string('currency_code', 3)->nullable()->after('amount');
		});
	}
	if (
		!Schema::hasColumn('payments', 'period_start')
		&& Schema::hasColumn('payments', 'currency_code')
	) {
		Schema::table('payments', function (Blueprint $table) {
			$table->timestamp('period_start')
				->after('currency_code');
		});
	}
	if (
		!Schema::hasColumn('payments', 'period_end')
		&& Schema::hasColumn('payments', 'period_start')
	) {
		Schema::table('payments', function (Blueprint $table) {
			$table->timestamp('period_end')
				->after('period_start');
		});
	}
	if (
		!Schema::hasColumn('payments', 'canceled_at')
		&& Schema::hasColumn('payments', 'period_end')
	) {
		Schema::table('payments', function (Blueprint $table) {
			$table->timestamp('canceled_at')->nullable()
				->comment('Canceled by the user before the period end')
				->after('period_end');
		});
	}
	if (
		!Schema::hasColumn('payments', 'refunded_at')
		&& Schema::hasColumn('payments', 'canceled_at')
	) {
		Schema::table('payments', function (Blueprint $table) {
			$table->timestamp('refunded_at')->nullable()->after('canceled_at');
		});
	}
	checkAndDropIndex('payments', 'post_id');
	if (
		Schema::hasColumn('payments', 'payable_id')
		&& Schema::hasColumn('payments', 'payable_type')
		&& Schema::hasColumn('payments', 'canceled_at')
		&& Schema::hasColumn('payments', 'refunded_at')
	) {
		Schema::table('payments', function (Blueprint $table) {
			$tableName = DBTool::table('payments');
			
			// Create indexes
			$indexes = ['payable_id', 'payable_type', 'transaction_id', 'period_start', 'period_end', 'canceled_at', 'refunded_at'];
			foreach ($indexes as $index) {
				$indexName = $tableName . '_' . $index . '_index';
				$sql = 'SHOW KEYS FROM ' . $tableName . ' WHERE Key_name="' . $indexName . '"';
				$keyExists = DB::select($sql);
				if (!$keyExists) {
					$table->index([$index], $indexName);
				}
			}
		});
	}
	
	if (
		Schema::hasColumn('packages', 'promotion_time')
		&& Schema::hasColumn('payments', 'package_id')
		&& Schema::hasColumn('payments', 'payable_type')
		&& Schema::hasColumn('payments', 'period_start')
		&& Schema::hasColumn('payments', 'period_end')
	) {
		DB::table('payments')->lazyById()->each(function ($payment) {
			$package = \App\Models\Package::find($payment->package_id);
			if (!empty($package)) {
				$isValidPromotionTime = (
					isset($package->promotion_time)
					&& is_numeric($package->promotion_time)
					&& $package->promotion_time > 0
				);
				if ($isValidPromotionTime) {
					$promotionTime = $package->promotion_time;
					// $promotionTime = 900;
					
					$today = \Illuminate\Support\Carbon::now(\App\Helpers\Date::getAppTimeZone());
					$today = $today->startOfDay();
					
					$periodStart = new \Illuminate\Support\Carbon($payment->created_at);
					$periodStart->timezone(\App\Helpers\Date::getAppTimeZone());
					$periodStart = $periodStart->startOfDay();
					
					$periodEnd = new \Illuminate\Support\Carbon($payment->created_at);
					$periodEnd->timezone(\App\Helpers\Date::getAppTimeZone());
					$periodEnd = $periodEnd->addDays($promotionTime);
					$periodEnd = $periodEnd->endOfDay();
					
					// $canceledAt = $periodEnd->lte($today) ? $periodEnd : null;
					$canceledAt = null;
					DB::table('payments')->where('id', $payment->id)
						->update([
							'payable_type'   => 'App\Models\Post',
							'period_start'   => $periodStart,
							'period_end'     => $periodEnd,
							'canceled_at'    => $canceledAt,
						]);
				}
			}
		});
	}
	
	//...
	
	// posts
	if (!Schema::hasColumn('posts', 'payment_id')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->bigInteger('payment_id')->unsigned()->nullable()
				->comment('The ID of the subscription used to post the listing')
				->after('user_id');
		});
	}
	
	// users
	if (!Schema::hasColumn('users', 'featured')) {
		Schema::table('users', function (Blueprint $table) {
			$table->boolean('featured')->nullable()->default('0')
				->comment('Need to be cleared form a cron tab command')
				->after('time_zone');
		});
	}
	
	//...
	
	// fields
	$connexion = config('database.default');
	$charset = config('database.connections.' . $connexion . '.charset', 'utf8mb4');
	$collation = config('database.connections.' . $connexion . '.collation', 'utf8mb4_unicode_ci');
	$table = DB::getTablePrefix() . 'fields';
	$sql = "ALTER TABLE `$table` CHANGE `belongs_to` `belongs_to` ENUM('post','posts') CHARACTER SET $charset COLLATE $collation NOT NULL DEFAULT 'post';";
	DB::statement($sql);
	
	DB::table('fields')->lazyById()->each(function ($field) {
		DB::table('fields')->where('id', $field->id)->update(['belongs_to' => 'post']);
	});
	
	$sql = "ALTER TABLE `$table` CHANGE `belongs_to` `belongs_to` ENUM('post','user') CHARACTER SET $charset COLLATE $collation NOT NULL DEFAULT 'post';";
	DB::statement($sql);
	
} catch (Exception $e) {
	
	dump($e->getMessage());
	dd('in ' . str_replace(base_path(), '', __FILE__));
	
}
