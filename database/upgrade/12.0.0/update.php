<?php

use App\Helpers\DBTool;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

try {
	
	/* FILES */
	File::deleteDirectory(app_path('Helpers/Auth/'));
	File::delete(app_path('Http/Controllers/Web/Auth/Traits/WebBasedLoginTrait.php'));
	File::delete(app_path('Http/Controllers/Admin/Auth/ResetPasswordController.php'));
	File::delete(app_path('Http/Requests/Admin/ResetPasswordRequest.php'));
	File::delete(app_path('Notifications/SendPasswordAndEmailVerification.php'));
	File::delete(resource_path('views/admin/auth/emails/password.blade.php'));
	File::delete(resource_path('views/admin/auth/passwords/reset.blade.php'));
	
	
	/* DATABASE */
	// users ==============================================
	if (
		Schema::hasColumn('users', 'email_verified_at')
		&& Schema::hasColumn('users', 'verified_email') /* Drop 'email_verified_at' only if 'verified_email' is available */
	) {
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('email_verified_at');
		});
	}
	if (!Schema::hasColumn('users', 'phone_verified_at')) {
		Schema::table('users', function (Blueprint $table) {
			$table->timestamp('phone_verified_at')->nullable()->useCurrentOnUpdate()->after('phone_token');
		});
	}
	if (!Schema::hasColumn('users', 'email_verified_at')) {
		Schema::table('users', function (Blueprint $table) {
			$table->timestamp('email_verified_at')->nullable()->useCurrentOnUpdate()->after('phone_token');
		});
	}
	if (!Schema::hasColumn('users', 'auth_field')) {
		Schema::table('users', function (Blueprint $table) {
			$table->enum('auth_field', ['email', 'phone'])->nullable()->default('email')->after('about');
		});
	}
	if (!Schema::hasColumn('users', 'phone_country')) {
		Schema::table('users', function (Blueprint $table) {
			$table->string('phone_country', 2)->nullable()->after('phone');
		});
	}
	if (!Schema::hasColumn('users', 'phone_national')) {
		Schema::table('users', function (Blueprint $table) {
			$table->string('phone_national', 30)->nullable()->after('phone');
		});
	}
	if (
		Schema::hasColumn('users', 'email_verified_at')
		&& Schema::hasColumn('users', 'phone_verified_at')
		&& Schema::hasColumn('users', 'verified_email')
		&& Schema::hasColumn('users', 'verified_phone')
		&& Schema::hasColumn('users', 'auth_field')
		&& Schema::hasColumn('users', 'phone_national')
		&& Schema::hasColumn('users', 'phone_country')
	) {
		DB::table('users')->lazyById()->each(function ($user) {
			$emailVerifiedAt = ($user->verified_email == 1) ? now()->format('Y-m-d H:i:s') : null;
			$phoneVerifiedAt = ($user->verified_phone == 1) ? now()->format('Y-m-d H:i:s') : null;
			$authField = (!empty($user->phone) && empty($user->email)) ? 'phone' : 'email';
			$phoneNational = (
				!empty($user->phone) && !empty($user->country_code)
				&& isValidPhoneNumber($user->phone, $user->country_code)
			)
				? phoneNational($user->phone, $user->country_code)
				: null;
			DB::table('users')->where('id', $user->id)
				->update([
					'email_verified_at' => $emailVerifiedAt,
					'phone_verified_at' => $phoneVerifiedAt,
					'auth_field'        => $authField,
					'phone_national'    => $phoneNational,
					'phone_country'     => $user->country_code,
				]);
		});
	}
	if (Schema::hasColumn('users', 'verified_email')) {
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('verified_email');
		});
	}
	if (Schema::hasColumn('users', 'verified_phone')) {
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('verified_phone');
		});
	}
	Schema::table('users', function (Blueprint $table) {
		$tableName = DBTool::table('users');
		
		// Check if a unique indexes key exist, and drop it
		$indexes = ['verified_email', 'verified_phone'];
		foreach ($indexes as $index) {
			$sql = 'SHOW KEYS FROM ' . $tableName . ' WHERE Key_name="' . $index . '"';
			$keyExists = DB::select($sql);
			if ($keyExists) {
				$table->dropIndex($index);
			}
		}
		
		// Create indexes
		$indexes = ['email_verified_at', 'phone_verified_at', 'auth_field', 'phone_country'];
		foreach ($indexes as $index) {
			$indexName = $tableName . '_' . $index . '_index';
			$sql = 'SHOW KEYS FROM ' . $tableName . ' WHERE Key_name="' . $indexName . '"';
			$keyExists = DB::select($sql);
			if (!$keyExists) {
				$table->index([$index], $indexName);
			}
		}
	});
	
	// posts ==============================================
	if (!Schema::hasColumn('posts', 'reviewed_at')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->timestamp('reviewed_at')->nullable()->after('tmp_token');
		});
	}
	if (!Schema::hasColumn('posts', 'archived_manually_at')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->timestamp('archived_manually_at')->nullable()->after('archived_at');
		});
	}
	if (!Schema::hasColumn('posts', 'phone_verified_at')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->timestamp('phone_verified_at')->nullable()->useCurrentOnUpdate()->after('tmp_token');
		});
	}
	if (!Schema::hasColumn('posts', 'email_verified_at')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->timestamp('email_verified_at')->nullable()->after('tmp_token');
		});
	}
	if (!Schema::hasColumn('posts', 'auth_field')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->enum('auth_field', ['email', 'phone'])->nullable()->default('email')->after('negotiable');
		});
	}
	if (!Schema::hasColumn('posts', 'phone_country')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->string('phone_country', 2)->nullable()->after('phone');
		});
	}
	if (!Schema::hasColumn('posts', 'phone_national')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->string('phone_national', 30)->nullable()->after('phone');
		});
	}
	if (
		Schema::hasColumn('posts', 'email_verified_at')
		&& Schema::hasColumn('posts', 'phone_verified_at')
		&& Schema::hasColumn('posts', 'reviewed_at')
		&& Schema::hasColumn('posts', 'archived_at')
		&& Schema::hasColumn('posts', 'archived_manually_at')
		&& Schema::hasColumn('posts', 'verified_email')
		&& Schema::hasColumn('posts', 'verified_phone')
		&& Schema::hasColumn('posts', 'reviewed')
		&& Schema::hasColumn('posts', 'archived')
		&& Schema::hasColumn('posts', 'auth_field')
		&& Schema::hasColumn('posts', 'phone_national')
		&& Schema::hasColumn('posts', 'phone_country')
	) {
		DB::table('posts')->lazyById()->each(function ($post) {
			$emailVerifiedAt = ($post->verified_email == 1) ? now()->format('Y-m-d H:i:s') : null;
			$phoneVerifiedAt = ($post->verified_phone == 1) ? now()->format('Y-m-d H:i:s') : null;
			$reviewedAt = ($post->reviewed == 1) ? now()->format('Y-m-d H:i:s') : null;
			$archivedAt = ($post->archived == 1)
				? ((isset($post->archived_at) && !empty($post->archived_at)) ? $post->archived_at : now()->format('Y-m-d H:i:s'))
				: null;
			$archivedManuallyAt = ($post->archived_manually == 1) ? now()->format('Y-m-d H:i:s') : null;
			$authField = (!empty($post->phone) && empty($post->email)) ? 'phone' : 'email';
			$phoneNational = (
				!empty($post->phone) && !empty($post->country_code)
				&& isValidPhoneNumber($post->phone, $post->country_code)
			)
				? phoneNational($post->phone, $post->country_code)
				: null;
			DB::table('posts')->where('id', $post->id)
				->update([
					'email_verified_at'    => $emailVerifiedAt,
					'phone_verified_at'    => $phoneVerifiedAt,
					'reviewed_at'          => $reviewedAt,
					'archived_at'          => $archivedAt,
					'archived_manually_at' => $archivedManuallyAt,
					'auth_field'           => $authField,
					'phone_national'       => $phoneNational,
					'phone_country'        => $post->country_code,
				]);
		});
	}
	if (Schema::hasColumn('posts', 'verified_email')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->dropColumn('verified_email');
		});
	}
	if (Schema::hasColumn('posts', 'verified_phone')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->dropColumn('verified_phone');
		});
	}
	if (Schema::hasColumn('posts', 'reviewed')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->dropColumn('reviewed');
		});
	}
	if (Schema::hasColumn('posts', 'archived')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->dropColumn('archived');
		});
	}
	if (Schema::hasColumn('posts', 'archived_manually')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->dropColumn('archived_manually');
		});
	}
	Schema::table('posts', function (Blueprint $table) {
		$tableName = DBTool::table('posts');
		
		// Check if a unique indexes key exist, and drop it
		$indexes = ['verified_email', 'verified_phone', 'reviewed', 'archived'];
		foreach ($indexes as $index) {
			$sql = 'SHOW KEYS FROM ' . $tableName . ' WHERE Key_name="' . $index . '"';
			$keyExists = DB::select($sql);
			if ($keyExists) {
				$table->dropIndex($index);
			}
		}
		
		// Create indexes
		$indexes = ['email_verified_at', 'phone_verified_at', 'reviewed_at', 'archived_at', 'auth_field', 'phone_country'];
		foreach ($indexes as $index) {
			$indexName = $tableName . '_' . $index . '_index';
			$sql = 'SHOW KEYS FROM ' . $tableName . ' WHERE Key_name="' . $indexName . '"';
			$keyExists = DB::select($sql);
			if (!$keyExists) {
				$table->index([$index], $indexName);
			}
		}
	});
	
	// password_resets ========================================
	if (!Schema::hasColumn('password_resets', 'phone_country')) {
		Schema::table('password_resets', function (Blueprint $table) {
			$table->string('phone_country', 2)->nullable()->after('phone');
		});
	}
	
	// countries ==============================================
	if (Schema::hasColumn('countries', 'phone')) {
		Schema::table('countries', function (Blueprint $table) {
			$table->string('phone', 100)->nullable()->change();
		});
	}
	
	
} catch (\Exception $e) {
	dump($e->getMessage());
	dd('in ' . str_replace(base_path(), '', __FILE__));
}
