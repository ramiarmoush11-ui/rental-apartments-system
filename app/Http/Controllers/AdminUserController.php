<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * Dashboard + جميع المستخدمين
     */
    public function index()
    {
        $users = User::latest()->get();

        return view('admin.dashboard', compact('users'));
    }

    /**
     * المستخدمين غير الموثقين (Pending)
     */
    public function pending()
    {
        $users = User::where('verified', 0)->latest()->get();

        return view('admin.dashboard', compact('users'));
    }

    /**
     * الموافقة على المستخدم
     */
    public function approve($id)
    {
        $user = User::findOrFail($id);
        $user->verified = 1;
        $user->save();

        return redirect()->back()->with('success', 'User approved successfully');
    }

    /**
     * رفض المستخدم (حذف)
     */
    public function reject($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->back()->with('success', 'User rejected successfully');
    }

    /**
     * حظر المستخدم
     */
    public function ban(Request $request, $id)
    {
        $request->validate([
            'ban_type' => 'required|in:Temporary,Permanent',
            'banned_until' => 'nullable|date'
        ]);

        $user = User::findOrFail($id);

        $user->isbanned = 1;
        $user->ban_type = $request->ban_type;
        $user->ban_count += 1;

        if ($request->ban_type === 'Temporary') {
            $user->banned_until = Carbon::parse($request->banned_until);
        } else {
            $user->banned_until = null;
        }

        $history = $user->ban_reasons_history ?? [];
        $history[] = [
            'reason' => $request->reason ?? 'No reason',
            'date'   => now()->toDateTimeString(),
            'admin'  => auth()->id(),
        ];

        $user->ban_reasons_history = $history;
        $user->save();

        return redirect()->back()->with('success', 'User banned successfully');
    }

    /**
     * فك الحظر
     */
    public function unban($id)
    {
        $user = User::findOrFail($id);

        $user->isbanned = 0;
        $user->banned_until = null;
        $user->ban_type = null;

        $user->save();

        return redirect()->back()->with('success', 'User unbanned successfully');
    }
}
