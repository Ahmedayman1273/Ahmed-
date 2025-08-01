<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // Get profile data
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'name'               => $user->name,
            'email'              => $user->email,
            'phone_number'       => $user->phone_number,
            'major'              => $user->major,
            'type'               => ucfirst($user->type),
            'profile_photo_url'  => $user->profile_photo_path
                ? url('storage/' . $user->profile_photo_path)
                : url('images/default_avatar.png'),
            'cover_photo_url'    => url('images/cover.jpg'),
        ]);
    }

    // Upload new profile photo
    public function uploadPhoto(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);

        if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('photo')->store('profile_photos', 'public');

        $user->update([
            'profile_photo_path' => $path,
        ]);

        return response()->json([
            'message' => 'Profile photo updated successfully.',
            'profile_photo_url' => url('storage/' . $path),
        ]);
    }

    // Delete profile photo
    public function deletePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->update([
            'profile_photo_path' => null,
        ]);

        return response()->json([
            'message' => 'Profile photo removed. Default photo will be used.',
            'profile_photo_url' => url('images/default_avatar.png'),
        ]);
    }
}
