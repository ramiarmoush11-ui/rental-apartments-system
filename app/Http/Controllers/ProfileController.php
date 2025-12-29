<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileStoreRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{

    public function store(ProfileStoreRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('users/avatar', 'public');
            $validatedData['avatar'] = $path;
        }

        if ($request->hasFile('idPhoto')) {
            $path = $request->file('idPhoto')->store('users/idPhoto', 'public');
            $validatedData['idPhoto'] = $path;
        }

        $profile = Profile::create($validatedData);
        return response()->json([
            'message' => __('profile.created_successfully'),
            'profile' =>new ProfileResource($profile)
        ], 201);
    }

    public function update(ProfileUpdateRequest $request)
    {
        $profile = Auth::user()->profile;
        $validatedData = $request->validated();

        if (!$profile) {
            return response()->json(['message' => __('profile.not_found')], 404);
        }

        if ($request->hasFile('avatar')) {
            if ($profile->avatar) {
                Storage::disk('public')->delete($profile->avatar);
            }
            $path = $request->file('avatar')->store('users/avatar', 'public');
            $validatedData['avatar'] = $path;
        }
        $profile->update($validatedData);

        return response()->json([
            'message' => __('profile.updated_successfully'),
            'profile' => new ProfileResource($profile)
        ], 200);
    }
    public function show(Request $request)
{
    $user = Auth::user(); 

    if (!$user || !$user->profile) {
        return response()->json([
            'message' => __('profile.not_found')
        ], 404);
    }

    return response()->json([
        'profile' => new ProfileResource($user->profile)
    ], 200);
}

}
