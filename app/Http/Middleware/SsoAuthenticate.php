<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Str;

class SsoAuthenticate
{
    public function handle(Request $request, Closure $next, string $appName = 'E-RANDIS')
    {
        $ssoToken = $request->query('sso_token');

        if ($ssoToken) {
            $decoded = $this->decodeJwt($ssoToken);
            if ($decoded && isset($decoded['user_id'])) {
                $permissions = $decoded['app_permissions'] ?? [];
                
                if (!isset($permissions[$appName])) {
                    return redirect('https://auth.sipat-donggala.my.id/auth/login')
                        ->with('error', "Anda tidak memiliki hak akses untuk aplikasi $appName.");
                }

                $user = User::firstOrCreate(
                    ['email' => $decoded['email']],
                    [
                        'name'     => $decoded['nama'],
                        'password' => bcrypt(Str::random(16)),
                        'role'     => $permissions[$appName]['role'] ?? 'user',
                    ]
                );

                Auth::login($user);

                // Redirect to clean URL
                return redirect()->to($request->url());
            }
        }

        if (!Auth::check()) {
            $ssoUrl = 'https://auth.sipat-donggala.my.id/auth/login';
            return redirect($ssoUrl . '?redirect_to=' . urlencode($request->fullUrl()));
        }

        return $next($request);
    }

    private function decodeJwt(string $jwt): ?array
    {
        $secretKey = 'SIPAT_SSO_SECRET_KEY_JWT_2026_SECURE_TOKEN_DONGGALA';
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) return null;

        $sig = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', $parts[0] . '.' . $parts[1], $secretKey, true)));
        if (!hash_equals($sig, $parts[2])) return null;

        return $payload;
    }
}
