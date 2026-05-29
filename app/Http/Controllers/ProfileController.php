<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controller untuk Manajemen Profil Pengguna
 */
class ProfileController extends Controller implements HasMiddleware
{
    /**
     * Mendapatkan middleware yang ditugaskan ke controller ini.
     * 
     * @return array
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Menampilkan halaman profil.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('profile.index', [
            'user' => auth()->user()
        ]);
    }

    /**
     * Memperbarui profil pengguna.
     * 
     * @param UpdateProfileRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();

        $validated = $request->validated();

        // Update data dasar
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Update password jika diisi
        if ($request->filled('password')) {
            $user->password = $validated['password']; // hashed via model cast
        }

        // Update avatar jika diunggah
        if ($request->hasFile('avatar')) {
            // Hapus avatar lama jika ada
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return redirect()->route('profile.index')->with('success', 'Profil Anda berhasil diperbarui.');
    }
}
