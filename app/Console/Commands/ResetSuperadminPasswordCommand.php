<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetSuperadminPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipat:reset-superadmin 
                            {--email=admin@example.com : Email akun superadmin} 
                            {--password= : Password baru (opsional, jika kosong akan di-generate otomatis)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ganti password akun superadmin dengan aman untuk mengamankan sistem produksi';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email') ?: 'admin@example.com';
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Pengguna dengan email '{$email}' tidak ditemukan.");
            return self::FAILURE;
        }

        $newPassword = $this->option('password');
        $generated = false;

        if (empty($newPassword)) {
            $newPassword = Str::password(16, true, true, false, false);
            $generated = true;
        }

        $user->password = Hash::make($newPassword);
        // Hapus password lama yang tersimpan di plain_password
        $user->plain_password = null;
        $user->save();

        $this->info("==================================================");
        $this->info("✅ Password Superadmin Berhasil Diperbarui!");
        $this->info("--------------------------------------------------");
        $this->line("Nama  : " . $user->name);
        $this->line("Email : " . $user->email);
        $this->line("Role  : " . $user->role->value);
        if ($generated) {
            $this->warn("Password Baru (Acak & Aman) : " . $newPassword);
            $this->comment("Simpan password ini di tempat yang aman (Password Manager).");
        } else {
            $this->info("Password Baru : [Sesuai input yang diberikan]");
        }
        $this->info("==================================================");

        return self::SUCCESS;
    }
}
