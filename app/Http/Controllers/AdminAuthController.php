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
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);


        if (!Auth::attempt($credentials)) {
            return back()->withErrors([
                'email' => 'Invalid credentials. Please check your email or password.'
            ]);
        }


        $user = Auth::user();


        if (!$user) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Login failed. User not found.'
            ]);
        }


        if ($user->enRole !== 'Admin') {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Access denied. You are not authorized as Admin.'
            ]);
        }


        if ($user->isbanned) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Your account is banned. Please contact support.'
            ]);
        }


        return redirect()->route('admin.dashboard')
            ->with('status', 'Welcome back, Admin!');
    }

    
    public function logout()
    {
        if (Auth::check()) {
            Auth::logout();
        }

        return redirect()->route('admin.login')
            ->with('status', 'You have been logged out successfully.');
    }
}
