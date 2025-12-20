<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {

            $user = Auth::user();

            // تحقق أنه Admin
            if ($user->enRole !== 'Admin') {
                Auth::logout();
                return back()->withErrors(['email' => 'Access denied']);
            }

            // تحقق مو محظور
            if ($user->isbanned) {
                Auth::logout();
                return back()->withErrors(['email' => 'Account is banned']);
            }

            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials']);
    }

    public function logout()
{
    Auth::logout();
    return redirect()->route('admin.login');
}
}
