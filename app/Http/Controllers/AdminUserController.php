<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{


   public function show($id)
{
    $user = User::with(['profile', 'bookings', 'payments'])->find($id);

    if (!$user) {
        return redirect()->back()->withErrors(['error' => 'User not found']);
    }

    // حول الـ Resource إلى Array
    $userData = (new UserResource($user))->toArray(request());

    // تأكد إنو البروفايل كمان صار Array
    if (isset($userData['Profile']) && $userData['Profile'] instanceof \Illuminate\Http\Resources\Json\JsonResource) {
        $userData['Profile'] = $userData['Profile']->toArray(request());
    }

    return view('admin.user_details', [
        'user' => $userData
    ]);
}
    public function index()
    {
        $users = User::where('verified', true)
            ->where('enRole', '!=', 'Admin')
            ->where('isbanned', false)
            ->latest()
            ->paginate(10);

        return view('admin.dashboard', [
            'users' => $users,
            'section' => 'users'
        ]);
    }

    public function pending()
    {
        $users = User::where('verified', false)
            ->where('enRole', '!=', 'Admin')
            ->latest()
            ->get();

        return view('admin.dashboard', [
            'users' => $users,
            'section' => 'pending'
        ]);
    }

    public function banned()
    {
        $users = User::where('isbanned', true)
            ->where('enRole', '!=', 'Admin')
            ->latest()
            ->paginate(10);

        return view('admin.dashboard', [
            'users' => $users,
            'section' => 'banned'
        ]);
    }
    /**
     * الموافقة على المستخدم
     */
    public function approve($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found']);
        }

        $user->update(['verified' => true]);

        return redirect()->back()->with('success', 'User approved successfully');
    }

    /**
     * رفض المستخدم (حذف)
     */
    public function reject($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found']);
        }

        $user->delete();

        return redirect()->back()->with('success', 'User rejected successfully');
    }

    /**
     * حظر المستخدم
     */
    public function ban(Request $request, $id)
    {
        // تحقق من المدخلات
        $validated = $request->validate([
            'ban_type'      => 'required|in:Temporary,Permanent',
            'banned_until'  => 'nullable|date',
            'reason'        => 'required|string|max:255',
        ]);

        $user = User::find($id);

        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found']);
        }

        // إذا الحظر مؤقت لازم يدخل تاريخ
        if ($validated['ban_type'] === 'Temporary' && empty($validated['banned_until'])) {
            return redirect()->back()->withErrors(['error' => 'You must provide a date for temporary ban']);
        }

        $user->isbanned   = true;
        $user->ban_type   = $validated['ban_type'];
        $user->ban_count  = $user->ban_count + 1;

        // إذا الحظر مؤقت → حدد تاريخ الانتهاء
        if ($validated['ban_type'] === 'Temporary') {
            $user->banned_until = Carbon::parse($validated['banned_until']);
        } else {
            $user->banned_until = null;
        }

        // سجل تاريخ وأسباب الحظر
        $history = $user->ban_reasons_history ?? [];
        $history[] = [
            'reason' => $validated['reason'],
            'date'   => now()->toDateTimeString(),
            'admin'  => auth()->id(),
        ];

        $user->ban_reasons_history = $history;
        $user->save();

        return redirect()->back()->with('success', 'User banned successfully');
    }

    /**
     * فك الحظر عن المستخدم
     */
    public function unban($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found']);
        }

        $user->update([
            'isbanned'      => false,
            'banned_until'  => null,
            'ban_type'      => null,
        ]);

        return redirect()->back()->with('success', 'User unbanned successfully');
    }
}
