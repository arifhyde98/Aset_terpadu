<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Panggil Seeder Bawaan Sistem
        $this->call([
            SettingSeeder::class,
            OpdSeeder::class,
            VehicleTypeSeeder::class,
            ReportSettingSeeder::class,
            SamplePolygonSeeder::class,
            UnrecordedLandSeeder::class,
        ]);

        // 2. Buat Akun Superadmin Utama agar Terhindar dari Lock Mode (jika belum ada)
        if (!User::where('email', 'admin@example.com')->exists()) {
            $defaultPassword = env('ADMIN_DEFAULT_PASSWORD', \Illuminate\Support\Str::password(16));
            User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => 'superadmin',
                'password' => Hash::make($defaultPassword),
            ]);
        }
    }
}
