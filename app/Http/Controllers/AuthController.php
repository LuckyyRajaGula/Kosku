<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const SEEDED_CREDENTIAL_HINTS = [
        ['role' => 'pemilik', 'username' => 'budi.pemilik', 'password' => 'pemilik123'],
        ['role' => 'pengelola', 'username' => 'siti.pengelola', 'password' => 'pengelola123'],
        ['role' => 'penyewa', 'username' => 'ahmad.penyewa', 'password' => 'penyewa123'],
    ];

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('kosku_user')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', [
            'demoAccounts' => self::SEEDED_CREDENTIAL_HINTS,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = DB::table('user')->where('username', $validated['username'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return back()->withInput(['username' => $validated['username']])->with('error', 'Username atau password salah.');
        }

        $request->session()->put('kosku_user', [
            'id' => $user->id_user,
            'nama' => $user->nama,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('kosku_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Berhasil logout.');
    }
}
