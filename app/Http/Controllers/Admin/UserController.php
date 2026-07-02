<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Daftar semua pengguna + form tambah pengguna.
     */
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->withCount('documents')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Buat akun staf baru. Password boleh dikosongkan — digenerate otomatis
     * dan ditampilkan SEKALI via flash agar admin menyalinnya ke staf.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $password = $validated['password'] ?? Str::password(12, symbols: false);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => bcrypt($password),
            // Email diverifikasi langsung: akun dibuat internal oleh admin,
            // route aplikasi memakai middleware 'verified'.
            'email_verified_at' => now(),
        ]);

        return redirect()->route('users.index')
            ->with('success', "Akun {$user->name} berhasil dibuat.")
            ->with('generated_credentials', [
                'email' => $user->email,
                'password' => $password,
            ]);
    }

    /**
     * Ubah peran admin/operator. Admin tidak bisa mengubah perannya sendiri
     * agar sistem tidak kehilangan admin terakhir.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Anda tidak dapat mengubah peran akun sendiri.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR])],
        ]);

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "Peran {$user->name} diubah menjadi {$validated['role']}.");
    }

    /**
     * Reset password: generate baru dan tampilkan SEKALI via flash.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $password = Str::password(12, symbols: false);

        $user->update(['password' => bcrypt($password)]);

        return back()
            ->with('success', "Password {$user->name} berhasil di-reset.")
            ->with('generated_credentials', [
                'email' => $user->email,
                'password' => $password,
            ]);
    }

    /**
     * Aktifkan / nonaktifkan akun. Tidak boleh menonaktifkan akun sendiri.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active
            ? "Akun {$user->name} diaktifkan kembali."
            : "Akun {$user->name} dinonaktifkan.");
    }
}
