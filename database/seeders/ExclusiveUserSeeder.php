<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ExclusiveUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'operator@sentinel.grok'],
            [
                'name' => 'Sentinel Operator',
                'password' => Hash::make('QuantumSecure2026'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_first_login' => false,
                'validation_status' => 'approved',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@sentinel.grok'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('AdminSecure2026'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_first_login' => false,
                'validation_status' => 'approved',
            ]
        );
    }
}
