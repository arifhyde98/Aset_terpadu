<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General / Brand (Dua Logo: Kiri & Kanan)
            ['key' => 'site_logo', 'value' => null, 'type' => 'image', 'group' => 'general'],
            ['key' => 'site_logo_right', 'value' => null, 'type' => 'image', 'group' => 'general'],
            ['key' => 'site_name', 'value' => 'SIPAT TERPADU', 'type' => 'text', 'group' => 'general'],
            ['key' => 'site_subtitle', 'value' => 'Sistem Informasi Aset Pemerintah Daerah', 'type' => 'text', 'group' => 'general'],

            // Landing Page
            ['key' => 'hero_title', 'value' => 'SIPAT Terpadu', 'type' => 'text', 'group' => 'landing'],
            ['key' => 'hero_subtitle', 'value' => 'Sistem Informasi Pertanahan, Kendaraan Dinas, & Labelisasi Dokumen', 'type' => 'text', 'group' => 'landing'],
            ['key' => 'hero_description', 'value' => 'Platform digital terintegrasi untuk efisiensi, transparansi, dan akuntabilitas manajemen aset daerah, kendaraan dinas, dan pengarsipan dokumen.', 'type' => 'textarea', 'group' => 'landing'],
            ['key' => 'hero_image', 'value' => 'images/hero-illustration.png', 'type' => 'image', 'group' => 'landing'],
            ['key' => 'hero_bg_image', 'value' => 'images/hero-illustration.png', 'type' => 'image', 'group' => 'landing'],
            
            // Login Page
            ['key' => 'login_title', 'value' => 'SIPAT Terpadu', 'type' => 'text', 'group' => 'login'],
            ['key' => 'login_subtitle', 'value' => 'Sistem Informasi Pertanahan, Kendaraan Dinas, & Labelisasi Dokumen', 'type' => 'text', 'group' => 'login'],
            ['key' => 'login_description', 'value' => 'Platform digital terintegrasi untuk efisiensi, transparansi, dan akuntabilitas manajemen aset daerah, kendaraan dinas, dan pengarsipan dokumen.', 'type' => 'textarea', 'group' => 'login'],
            ['key' => 'login_bg_image', 'value' => 'images/hero-illustration.png', 'type' => 'image', 'group' => 'login'],
            ['key' => 'login_logo_icon', 'value' => 'bi-shield-lock-fill', 'type' => 'text', 'group' => 'login'],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
