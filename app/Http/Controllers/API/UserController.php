<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // Update profile image
    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $user = $request->user();

        $image = $request->file('profile_image');
        $imageName = uniqid() . '_' . time() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('profile_images', $imageName, 'public');

        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }

        $user->profile_image = $path;
        $user->save();

        return response()->json([
            'message' => 'Profile image updated successfully.',
            'profile_image_url' => asset('storage/' . $path),
        ]);
    }
}
