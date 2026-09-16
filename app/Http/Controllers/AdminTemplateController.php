<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminTemplateController extends Controller
{
    /**
     * Memperbarui preferensi template layout admin pengguna.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template' => 'required|string|in:classic,executive,horizontal,glass,minimalist',
        ]);

        $template = $validated['template'];

        // Simpan di Session
        session(['admin_template' => $template]);

        // Jika terotentikasi, simpan di database User
        if (auth()->check()) {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'admin_template')) {
                $user->update(['admin_template' => $template]);
            }
        }

        return response()->json([
            'success' => true,
            'template' => $template,
            'message' => 'Template layout admin berhasil diperbarui.',
        ]);
    }
}
