<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test: Mengunggah logo baru otomatis menghapus logo lama dari disk.
     */
    public function test_uploading_new_logo_automatically_deletes_old_logo_from_disk()
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // 1. Buat folder dan file logo lama bohongan di public/uploads/settings
        $directory = public_path('uploads/settings');
        File::ensureDirectoryExists($directory);

        $oldFilename = 'old_logo_' . Str::random(10) . '.png';
        $oldPath = 'uploads/settings/' . $oldFilename;
        $oldFullPath = public_path($oldPath);
        File::put($oldFullPath, 'dummy logo content');

        // Pastikan file lama benar-benar ada di disk sebelum test berjalan
        $this->assertFileExists($oldFullPath);

        // 2. Set database agar setting 'site_logo' menunjuk ke logo lama tersebut
        $setting = Setting::updateOrCreate(
            ['key' => 'site_logo'],
            [
                'type' => 'image',
                'value' => $oldPath,
                'group' => 'general'
            ]
        );

        // 3. Siapkan file logo baru yang akan diunggah
        $newFile = UploadedFile::fake()->image('new_logo.png');

        // 4. Kirim request untuk update setting logo
        $response = $this->actingAs($superadmin)
            ->post(route('settings.update'), [
                'settings' => [
                    'site_logo' => $newFile,
                ]
            ]);

        // Assert response diarahkan kembali (redirect) dengan sukses
        $response->assertSessionHas('success');
        $response->assertRedirect();

        // 5. Verifikasi bahwa file logo lama di disk telah terhapus otomatis!
        $this->assertFileDoesNotExist($oldFullPath);

        // 6. Verifikasi database terupdate dengan path logo baru
        $setting->refresh();
        $newPath = $setting->value;
        $newFullPath = public_path($newPath);
        
        $this->assertNotEquals($oldPath, $newPath);
        $this->assertFileExists($newFullPath);

        // Cleanup: Hapus file baru agar tidak mengotori repository
        if (File::exists($newFullPath)) {
            File::delete($newFullPath);
        }
    }
}
