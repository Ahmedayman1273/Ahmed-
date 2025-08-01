<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\PasswordResetCode;

class PasswordResetController extends Controller
{
    // Send code to user's personal email
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->personal_email) {
            return response()->json(['message' => 'This user does not have a registered personal email'], 404);
        }

        $code = rand(1000, 9999);

        PasswordResetCode::updateOrCreate(
            ['email' => $user->email],
            [
                'code' => bcrypt($code),
                'expires_at' => now()->addMinutes(10),
            ]
        );

        try {
            Mail::raw("Your password reset code is: $code", function ($message) use ($user) {
                $message->to($user->personal_email)
                        ->subject('Password Reset Code');
            });

            return response()->json(['message' => 'The code has been sent to your personal email']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send email.'], 500);
        }
    }

    // Verify reset code
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code'  => 'required|string',
        ]);

        if (!$this->isValidCode($request->email, $request->code)) {
            return response()->json(['message' => 'Invalid or expired code'], 422);
        }

        PasswordResetCode::where('email', $request->email)->delete();

        return response()->json(['message' => 'Code is valid']);
    }

    // Reset password using code
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email|exists:users,email',
            'code'                  => 'required|string',
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ]);

        if (!$this->isValidCode($request->email, $request->code)) {
            return response()->json(['message' => 'Invalid or expired code'], 422);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        PasswordResetCode::where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully']);
    }

    // Internal helper to check code validity
    private function isValidCode($email, $inputCode)
    {
        $record = PasswordResetCode::where('email', $email)->first();

        if (!$record || now()->gt($record->expires_at)) {
            return false;
        }

        return Hash::check($inputCode, $record->code);
    }
}
