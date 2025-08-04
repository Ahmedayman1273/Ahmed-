<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Libraries\ImageValidator;

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
                ? asset('storage/' . $user->profile_photo_path)
                : asset('images/default_avatar.png'),
            'cover_photo_url'    => asset('images/cover.jpg'),
        ]);
    }

    // Upload new profile photo
    public function uploadPhoto(Request $request)
    {
        $user = $request->user();

        // Use custom image validator
        $validator = ImageValidator::validate($request, 'photo');
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check real MIME type
        $mime = $request->file('photo')->getMimeType();
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
            return response()->json(['error' => 'Invalid image type detected after upload.'], 400);
        }

        if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('photo')->store('profile_photos', 'public');

        $user->update([
            'profile_photo_path' => $path,
        ]);

        return response()->json([
            'message' => 'Profile photo updated successfully.',
            'profile_photo_url' => asset('storage/' . $path),
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
            'profile_photo_url' => asset('images/default_avatar.png'),
        ]);
    }
}
