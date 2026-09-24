<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ResetPasswordController extends Controller implements HasMiddleware
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | Controller ini bertanggung jawab untuk menangani permintaan penyetelan ulang
    | kata sandi dan menggunakan trait sederhana untuk menyertakan perilaku ini.
    | Anda bebas menjelajahi trait ini dan menimpa metode apa pun yang ingin
    | Anda sesuaikan.
    |
    */

    use ResetsPasswords;

    public static function middleware(): array
    {
        return [
            new Middleware('guest'),
            new Middleware('throttle:5,1', only: ['reset']),
        ];
    }

    /**
     * Tujuan pengalihan pengguna setelah berhasil menyetel ulang kata sandi mereka.
     *
     * @var string
     */
    protected $redirectTo = '/home';
}
