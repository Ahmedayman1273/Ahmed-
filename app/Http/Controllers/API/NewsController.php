<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Notifications\NewsOrEventCreated;


class NewsController extends Controller
{
    // Helper to convert image path to full URL
  private function getImageUrl($path)
    {
        return $path ? config('app.url') . '/storage/' . ltrim($path, '/') : null;
    }

    // Format single news item
 private function formatNews($news)
   {
        return [
            'id'        => $news->id,
            'title'     => $news->title,
            'content'   => $news->content,
            'created_at'=> $news->created_at,
            'image'     => $this->getImageUrl($news->image),
        ];
   }


 // Get all news items

 public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        $news = News::orderBy('created_at', 'desc')->get();
        $formatted = $news->map(fn($item) => $this->formatNews($item));

        return response()->json($formatted);
    }

 // Show single news item by ID
 public function show($id)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found.'
            ], 404);
        }

        return response()->json($this->formatNews($news));
    }

    // Create news item (admin only)
   public function store(Request $request)
{
    $user = auth()->user();

    if (!$user || $user->type !== 'admin') {
        return response()->json([
            'status' => 'error',
            'message' => 'Forbidden. Admins only.'
        ], 403);
    }

    $request->validate([
        'title'   => 'required|string|max:255',
        'content' => 'required|string',
        'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    $imagePath = $request->hasFile('image')
        ? $request->file('image')->store('news', 'public')
        : null;

    $news = News::create([
        'title'   => $request->title,
        'content' => $request->content,
        'image'   => $imagePath,
    ]);

    // إرسال إشعارات للمستخدمين
    $targets = User::whereIn('type', ['student', 'graduate'])->get();

    foreach ($targets as $target) {
        $target->notify(new NewsOrEventCreated([
            'title' => 'News Update',
            'message' => "A news item titled '{$news->title}' has been published.",
        ]));
    }

    return response()->json($this->formatNews($news), 201);
}

    // Update news item (admin only)
   public function update(Request $request, $id)
{
    $user = auth()->user();

    if (!$user || $user->type !== 'admin') {
        return response()->json([
            'status' => 'error',
            'message' => 'Forbidden. Admins only.'
        ], 403);
    }

    $news = News::find($id);
    if (!$news) {
        return response()->json([
            'status' => 'error',
            'message' => 'News not found.'
        ], 404);
    }

    $request->validate([
        'title'   => 'sometimes|required|string|max:255',
        'content' => 'sometimes|required|string',
        'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    if ($request->hasFile('image')) {
        if ($news->image) {
            Storage::disk('public')->delete($news->image);
        }
        $news->image = $request->file('image')->store('news', 'public');
    }

    if ($request->has('title')) {
        $news->title = $request->title;
    }

    if ($request->has('content')) {
        $news->content = $request->content;
    }

    $news->save();

    return response()->json($this->formatNews($news));
}


    // Delete news item (admin only)
    public function destroy($id)
{
    $user = auth()->user();

    if (!$user || $user->type !== 'admin') {
        return response()->json([
            'status' => 'error',
            'message' => 'Forbidden. Admins only.'
        ], 403);
    }

    $news = News::find($id);
    if (!$news) {
        return response()->json([
            'status' => 'error',
            'message' => 'News not found.'
        ], 404);
    }

    if ($news->image) {
        Storage::disk('public')->delete($news->image);
    }

    $news->delete();

    $newsList = News::latest()->get()->map(fn($item) => $this->formatNews($item));

    return response()->json([
        'message' => 'News deleted',
        'news'    => $newsList
    ]);
}

}
