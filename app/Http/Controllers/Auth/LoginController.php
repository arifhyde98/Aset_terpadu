<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoginController extends Controller implements HasMiddleware
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | Controller ini menangani otentikasi pengguna untuk aplikasi dan
    | mengarahkan mereka ke halaman utama dashboard. Controller ini menggunakan
    | trait untuk menyediakan fungsionalitas ini secara instan dan efisien.
    |
    */

    use AuthenticatesUsers;

    /**
     * Tujuan pengalihan pengguna setelah berhasil masuk.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Mendapatkan middleware yang ditugaskan ke controller ini.
     * 
     * @return array
     */
    public static function middleware(): array
    {
        return [
            new Middleware('guest', except: ['logout']),
            new Middleware('auth', only: ['logout']),
        ];
    }

    /**
     * Hook setelah pengguna berhasil masuk (login).
     * Membersihkan session filter SIPAT agar pengguna baru mendapatkan tampilan awal default yang bersih.
     */
    protected function authenticated(\Illuminate\Http\Request $request, $user)
    {
        $request->session()->forget('sipat_aset_filters');
        $request->session()->forget('sipat_aset_filters_' . $user->id);
    }

    /**
     * Hook setelah pengguna keluar (logout).
     */
    protected function loggedOut(\Illuminate\Http\Request $request)
    {
        $request->session()->forget('sipat_aset_filters');
    }
}
