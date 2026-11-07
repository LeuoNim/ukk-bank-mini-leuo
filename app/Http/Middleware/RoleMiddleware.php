<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // pastikan user sudah login
        if (! $request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.',
            ], 401);
        }

        // cek apakah role user saat ini ada dalam daftar role yang diizinkan untuk url ini
        $user = $request->user();
        if (! isset($user->role) || ! in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak! Jabatan (Role) Anda tidak memiliki izin untuk fitur ini',
            ], 403);
        }

        // cek apakah akun petugas masih aktif
        if (isset($user->status) && $user->status !== 'Aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun petugas tidak aktif atau telah dinonaktifkan.',
            ], 403);
        }

        // jika lolos izinkan sistem lanjut ke controller
        return $next($request);
    }
}
