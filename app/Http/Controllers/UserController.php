<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Opd;
use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controller untuk Manajemen Pengguna & Role (Khusus Superadmin)
 */
class UserController extends Controller implements HasMiddleware
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
            new Middleware('role:superadmin'),
        ];
    }

    /**
     * Menampilkan daftar pengguna dengan filter role.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = User::with('opd');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('q')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                  ->orWhere('email', 'like', '%' . $request->q . '%');
            });
        }

        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['name', 'email', 'role'];

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $users = $query->paginate(15)->withQueryString();
        $opds = Opd::orderBy('nama')->get();
        $roles = UserRole::cases();

        return view('users.index', compact('users', 'opds', 'roles'));
    }

    /**
     * Menyimpan pengguna baru.
     * 
     * @param StoreUserRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $validated['plain_password'] = $validated['password'];

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Memperbarui data pengguna.
     * 
     * @param UpdateUserRequest $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if (empty($validated['password'])) {
            unset($validated['password']);
            unset($validated['plain_password']);
        } else {
            $validated['plain_password'] = $validated['password'];
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Menghapus pengguna.
     * 
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        // Mencegah hapus diri sendiri
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * Generate akun otomatis untuk semua OPD yang belum memiliki akun.
     * 
     * @param \App\Services\AccountService $accountService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateAllOpdAccounts(\App\Services\AccountService $accountService)
    {
        // 1. Perpanjang batas waktu eksekusi & memori untuk proses bulk generation
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $opdsWithoutAccount = Opd::whereDoesntHave('user')->get();
        $count = 0;

        if ($opdsWithoutAccount->isEmpty()) {
            return redirect()->route('users.index')->with('info', 'Semua OPD sudah memiliki akun admin.');
        }

        // 2. Gunakan transaksi per-OPD untuk mencegah Deadlock pada MySQL
        foreach ($opdsWithoutAccount as $opd) {
            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($opd, $accountService) {
                    $accountService->createOpdAccount($opd);
                });
                $count++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Gagal generate akun OPD [ID: {$opd->id} - {$opd->nama}]: " . $e->getMessage());
            }
        }

        return redirect()->route('users.index')->with('success', "Berhasil men-generate {$count} akun admin OPD baru.");
    }

    /**
     * Reset password user ke password acak baru.
     * 
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(User $user)
    {
        $newPassword = 'DGL-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4));
        
        $user->update([
            'password' => $newPassword, // hashed via model cast
            'plain_password' => $newPassword, // encrypted via model cast
        ]);

        return redirect()->route('users.index')->with([
            'success' => "Password untuk {$user->name} berhasil di-reset.",
            'reset_password' => [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $newPassword
            ]
        ]);
    }
}
