<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Handle login
   public function login(Request $request)
{
    $rawHeader = $request->header('X-From');
    \Log::info('📩 X-From Header:', ['value' => $rawHeader]);

    $credentials = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ]);

    try {
        // Step 1: Check if email exists
        $userByEmail = \App\Models\User::where('email', $credentials['email'])->first();

        if (!$userByEmail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email is incorrect'
            ], 401);
        }

        // Step 2: Check if password matches
        if (!\Hash::check($credentials['password'], $userByEmail->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password is incorrect'
            ], 401);
        }

        // Step 3: Login user
        Auth::login($userByEmail);
        $user = Auth::user();

        // Step 4: Block student/graduate from web
        $source = strtolower($request->header('X-From') ?? 'web');
        if (
            in_array($user->type, ['student', 'graduate']) &&
            $source !== 'mobile'
        ) {
            Auth::logout();
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied from web'
            ], 403);
        }

        // Step 5: Remove old tokens for student/graduate
        if (in_array($user->type, ['student', 'graduate'])) {
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
        }

        // Step 6: Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token,
            'user'  => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'personal_email'   => $user->personal_email,
                'type'             => $user->type,
                'major'            => $user->major,
                'phone_number'     => $user->phone_number,
                'profile_photo'    => $user->profile_photo_path
                    ? asset('storage/' . $user->profile_photo_path)
                    : asset('images/default_avatar.png'),
                'created_at'       => $user->created_at,
                'updated_at'       => $user->updated_at,
            ]
        ]);

    } catch (\Throwable $e) {
        if (config('app.debug')) {
            return response()->json([
                'error'   => 'Something went wrong',
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }

        return response()->json([
            'error' => 'Something went wrong'
        ], 500);
    }
}


    // Handle logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    // Check if request is from web
    private function isWeb(Request $request): bool
    {
        return strtolower($request->header('X-From')) === 'mobile';
    }
}
