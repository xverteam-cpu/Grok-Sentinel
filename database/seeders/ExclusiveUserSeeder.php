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
                'is_first_login' => true,
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
                'is_first_login' => true,
                'validation_status' => 'approved',
            ]
        );

        User::updateOrCreate(
            ['email' => 'sawada.kazuki@sentinel.grok'],
            [
                'name' => 'サワダ カズキ',
                'password' => Hash::make('SawadaSecure2026!'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_first_login' => true,
                'validation_status' => 'approved',
                'withdrawable_balance' => 1065000.00,
            ]
        );

        User::updateOrCreate(
            ['email' => 'kennethnagac18@gmail.com'],
            [
                'name' => 'Kenneth Nagac',
                'password' => Hash::make('kepler-452b'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_first_login' => true,
                'validation_status' => 'approved',
                'withdrawable_balance' => 0,
            ]
        );

        User::updateOrCreate(
            ['email' => 'nagacclark@gmail.com'],
            [
                'name' => 'Nagac Clark',
                'password' => Hash::make('kepler-452b'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_first_login' => true,
                'validation_status' => 'approved',
                'withdrawable_balance' => 0,
            ]
        );
    }
}
