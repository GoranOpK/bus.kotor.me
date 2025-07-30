<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login'); // Pogled za admin login formu
    }

    public function login(Request $request)
    {
        // Validacija podataka
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Priprema kredencijala za autentikaciju
        $credentials = $request->only('username', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            // Uspješan login
            return redirect()->intended('/admin/dashboard');
        }

        // Neuspješan login
        return back()->withErrors([
            'username' => 'Pogrešno korisničko ime ili lozinka.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    }
}