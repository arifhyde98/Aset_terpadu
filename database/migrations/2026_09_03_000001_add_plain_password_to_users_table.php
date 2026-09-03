<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('plain_password')->nullable()->after('password');
        });

        // Set password terenkripsi untuk superadmin bawaan jika ada
        try {
            $adminUser = DB::table('users')->where('email', 'admin@example.com')->first();
            if ($adminUser) {
                DB::table('users')->where('id', $adminUser->id)->update([
                    'plain_password' => Crypt::encryptString('admin123'),
                ]);
            }
        } catch (\Throwable $e) {
            // Abaikan jika terjadi kendala saat encrypt seed awal
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('plain_password');
        });
    }
};
