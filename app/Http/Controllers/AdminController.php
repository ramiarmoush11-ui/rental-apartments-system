<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users()
    {
        $users = User::orderBy('created_at', 'desc')->get();

        if ($users->isEmpty()) {
            return view('admin.users')->with([
                'users' => [],
                'message' => 'No users found.'
            ]);
        }

        return view('admin.users', compact('users'));
    }
}
