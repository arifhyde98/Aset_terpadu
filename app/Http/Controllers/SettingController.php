<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Http\Requests\UpdateSettingRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controller untuk Manajemen Pengaturan Aplikasi (CMS)
 */
class SettingController extends Controller implements HasMiddleware
{
    /**
     * Mendapatkan middleware yang ditugaskan ke controller ini.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Menampilkan halaman daftar pengaturan yang dikelompokkan berdasarkan grup.
     */
    public function index(): View
    {
        $settings = Setting::all()->groupBy('group');
        return view('settings.index', compact('settings'));
    }

    /**
     * Memperbarui beberapa pengaturan sekaligus.
     */
    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $settingsInput = $request->input('settings', []);

        foreach (Setting::all() as $setting) {
            $key = $setting->key;

            if ($setting->type === 'image') {
                if ($request->hasFile("settings.$key")) {
                    $file = $request->file("settings.$key");
                    if ($file->isValid()) {
                        if ($setting->value && Str::startsWith($setting->value, 'uploads/')) {
                            File::delete(public_path($setting->value));
                        }

                        $directory = public_path('uploads/settings');
                        File::ensureDirectoryExists($directory);

                        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $file->move($directory, $filename);

                        $path = 'uploads/settings/' . $filename;
                        $setting->update(['value' => $path]);
                        cache()->forget("setting.{$key}");
                    }
                }
            } else {
                if (array_key_exists($key, $settingsInput)) {
                    $setting->update(['value' => $settingsInput[$key]]);
                    cache()->forget("setting.{$key}");
                }
            }
        }

        return redirect()->back()->with('success', 'Pengaturan Sistem berhasil diperbarui.');
    }
}
