<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ForgotPasswordController extends Controller implements HasMiddleware
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | Controller ini bertanggung jawab untuk menangani email penyetelan ulang
    | kata sandi dan menyertakan trait yang membantu dalam mengirimkan
    | notifikasi ini dari aplikasi Anda kepada pengguna Anda.
    |
    */

    use SendsPasswordResetEmails;

    public static function middleware(): array
    {
        return [
            new Middleware('guest'),
            new Middleware('throttle:5,1', only: ['sendResetLinkEmail']),
        ];
    }
}
