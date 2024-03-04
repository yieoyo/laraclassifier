<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create an example user
        DB::table('users')->insert(
            [
            'country_code' => 'US',
            'language_code' => 'en',
            'user_type_id' => 1, // Replace with the appropriate user type ID
            'gender_id' => 1, // Replace with the appropriate gender ID
            'name' => 'John Doe',
            'photo' => 'path/to/photo.jpg',
            'about' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            'auth_field' => 'email',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'), // Replace with the hashed password
            'remember_token' => Str::random(10),
            'is_admin' => true,
            'can_be_impersonated' => true,
            'disable_comments' => false,
            'create_from_ip' => '127.0.0.1', // Replace with the appropriate IP address
            'latest_update_ip' => '127.0.0.1', // Replace with the appropriate IP address
            'provider' => 'none', // Replace with the appropriate provider
            'provider_id' => null, // Replace with the appropriate provider ID
            'email_token' => Str::random(32),
            'phone_token' => Str::random(32),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'accept_terms' => true,
            'accept_marketing_offers' => false,
            'time_zone' => 'UTC',
            'featured' => false,
            'blocked' => false,
            'closed' => false,
            'last_activity' => now(),
            'last_login_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'country_code' => 'US',
            'language_code' => 'en',
            'user_type_id' => 1, // Replace with the appropriate user type ID
            'gender_id' => 1, // Replace with the appropriate gender ID
            'name' => 'John Doe',
            'photo' => 'path/to/photo.jpg',
            'about' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            'auth_field' => 'email',
            'email' => 'user@example.com',
            'password' => bcrypt('password'), // Replace with the hashed password
            'remember_token' => Str::random(10),
            'is_admin' => false,
            'can_be_impersonated' => true,
            'disable_comments' => false,
            'create_from_ip' => '127.0.0.1', // Replace with the appropriate IP address
            'latest_update_ip' => '127.0.0.1', // Replace with the appropriate IP address
            'provider' => 'none', // Replace with the appropriate provider
            'provider_id' => null, // Replace with the appropriate provider ID
            'email_token' => Str::random(32),
            'phone_token' => Str::random(32),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'accept_terms' => true,
            'accept_marketing_offers' => false,
            'time_zone' => 'UTC',
            'featured' => false,
            'blocked' => false,
            'closed' => false,
            'last_activity' => now(),
            'last_login_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // You can add more users as needed
    }
}
