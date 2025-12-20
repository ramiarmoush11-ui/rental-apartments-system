<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
 public function users()
    {
        // جلب كل المستخدمين من قاعدة البيانات
        $users = User::all();

        // تمريرهم إلى الـ Blade
        return view('admin.users', compact('users'));
    }
}
