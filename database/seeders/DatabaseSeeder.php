<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed accounts and the demo workspace data used for manual testing.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        foreach ([
            ['name' => 'Saka', 'email' => 'saka@example.com', 'system_role' => SystemRole::Admin],
            ['name' => 'Wifki', 'email' => 'wifki@example.com', 'system_role' => SystemRole::User],
            ['name' => 'Rizki', 'email' => 'rizki@example.com', 'system_role' => SystemRole::User],
            ['name' => 'Yudha', 'email' => 'yudha@example.com', 'system_role' => SystemRole::Auditor],
            ['name' => 'Dinda', 'email' => 'dinda@example.com', 'system_role' => SystemRole::User],
            ['name' => 'Bima', 'email' => 'bima@example.com', 'system_role' => SystemRole::User],
            ['name' => 'Nabila', 'email' => 'nabila@example.com', 'system_role' => SystemRole::User],
            ['name' => 'Fajar', 'email' => 'fajar@example.com', 'system_role' => SystemRole::User],
            ['name' => 'Tia', 'email' => 'tia@example.com', 'system_role' => SystemRole::User],
            ['name' => 'Bagus', 'email' => 'bagus@example.com', 'system_role' => SystemRole::User],
            ['name' => 'Intan', 'email' => 'intan@example.com', 'system_role' => SystemRole::User],
            ['name' => 'Rafi', 'email' => 'rafi@example.com', 'system_role' => SystemRole::Auditor],
        ] as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'system_role' => $account['system_role'],
                    'password' => $password,
                    'email_verified_at' => now(),
                ],
            );
        }

        $this->call(DemoDataSeeder::class);
    }
}
