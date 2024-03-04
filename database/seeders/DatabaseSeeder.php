<?php

namespace Database\Seeders;

// Increase the server resources
$iniConfigFile = __DIR__ . '/../../app/Helpers/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	include_once $iniConfigFile;
}

use App\Helpers\DBTool;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 *
	 * @return void
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function run()
	{
		// Code start execution time
		$startTime = now();
		
		DB::statement('SET FOREIGN_KEY_CHECKS=0;');
		
		// Truncate all tables
		$tables = DBTool::getDatabaseTables(DB::getTablePrefix());
		if (count($tables) > 0) {
			foreach ($tables as $table) {
				DB::statement('ALTER TABLE ' . $table . ' AUTO_INCREMENT=1;');
				
				// Don't truncate some tables (eg. migrations, ...)
				if (
					str_contains($table, 'migrations')
					|| str_contains($table, 'users')
				) {
					continue;
				}
				
				if (str_contains($table, 'blacklist')) {
					if (!isLocalEnv()) {
						continue;
					}
				}
				
				DB::statement('TRUNCATE TABLE ' . $table . ';');
			}
		}
		
		// Run Default Seeders
		$this->call(LanguageSeeder::class);
		$this->call(AdvertisingSeeder::class);
		$this->call(CategorySeeder::class);
		$this->call(ContinentSeeder::class);
		$this->call(CurrencySeeder::class);
		$this->call(FieldSeeder::class);
		$this->call(GenderSeeder::class);
		$this->call(HomeSectionSeeder::class);
		$this->call(PackageSeeder::class);
		$this->call(PageSeeder::class);
		$this->call(PaymentMethodSeeder::class);
		$this->call(PostTypeSeeder::class);
		$this->call(ReportTypeSeeder::class);
		$this->call(SettingSeeder::class);
		$this->call(UserTypeSeeder::class);
		$this->call(UserSeeder::class);
		$this->call(CategoryFieldSeeder::class);
		$this->call(CountrySeeder::class);
		
		$isDevOrDemoEnv = (isDevEnv() || isDemoEnv());
		if ($isDevOrDemoEnv) {
			$factoriesSeeders = [
				'\Database\Seeders\Factories\ClearFilesSeeder',
				'\Database\Seeders\Factories\PermissionDataSeeder',
				'\Database\Seeders\Factories\SettingDataSeeder',
				'\Database\Seeders\Factories\HomeDataSeeder',
				'\Database\Seeders\Factories\CountryDataSeeder',
				'\Database\Seeders\Factories\LanguageDataSeeder',
				'\Database\Seeders\Factories\MetaTagSeeder',
				'\Database\Seeders\Factories\PageSeeder',
				'\Database\Seeders\Factories\PostSeeder',
				'\Database\Seeders\Factories\FakerSeeder',
				'\Database\Seeders\Factories\MessengerSeeder',
				'\Database\Seeders\Factories\BlacklistSeeder',
			];
			
			foreach ($factoriesSeeders as $seeder) {
				if (str_contains($seeder, 'BlacklistSeeder')) {
					if (isLocalEnv()) {
						continue;
					}
				}
				
				if (class_exists($seeder)) {
					$this->call($seeder);
				}
			}
		}
		
		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
		
		// Get the code's execution's duration
		$this->execTimeLog($startTime->diffForHumans(now(), CarbonInterface::DIFF_ABSOLUTE, false, 3));
	}
	
	/**
	 * Code Execution Time Log
	 *
	 * @param $message
	 * @return void
	 */
	private function execTimeLog($message)
	{
		$message = 'Execution Time: ' . $message;
		
		$this->command->info($message);
		Log::channel('seeder')->info($message);
	}
}
