<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Faq;

class FaqController extends Controller
{
    // Get all questions only (for chatbot)
    public function questionsOnly(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        if ($user->type === 'admin') {
            return response()->json([
                'message' => 'Admins are not allowed to access this.'
            ], 403);
        }

        $questions = Faq::select('id', 'question')->latest()->get();
        return response()->json(['questions' => $questions]);
    }

    // Get answer to a specific question
    public function getAnswer(Request $request, $id)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        if ($user->type === 'admin') {
            return response()->json([
                'message' => 'Admins are not allowed to access this.'
            ], 403);
        }

        $faq = Faq::find($id);
        if (!$faq) {
            return response()->json(['message' => 'Question not found.'], 404);
        }

        return response()->json(['answer' => $faq->answer]);
    }
}
