<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Logout paksa user yang akunnya dinonaktifkan admin di tengah sesi.
     * Penolakan saat login (sesi baru) ditangani di LoginRequest.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Hanya bereaksi pada FALSE eksplisit: bila kolom belum ada
        // (kode ter-deploy sebelum migrate), nilai null dianggap aktif.
        if ($user && $user->is_active === false) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi admin.']);
        }

        return $next($request);
    }
}
