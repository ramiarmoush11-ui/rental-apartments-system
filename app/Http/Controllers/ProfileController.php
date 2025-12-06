<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileStoreRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'message' => 'proflie created successfully',
            'proflie' => $profile
        ], 201);
    }

    public function update(ProfileUpdateRequest $request)
    {
        $profile = Auth::user()->profile;

        if (!$profile) {
            return response()->json(['message' => 'profile not found'], 404);
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('users/avatar', 'public');
            $validatedData['avatar'] = $path;
        }

        /*if ($request->hasFile('idPhoto')) {
            $path = $request->file('idPhoto')->store('users/idPhoto', 'public');
            $validatedData['idPhoto'] = $path;
        }*/

        $profile->update($validatedData);

        return response()->json([
            'message' => 'profile updated successfully',
            'profile' => $profile
        ], 200);
    }

}
