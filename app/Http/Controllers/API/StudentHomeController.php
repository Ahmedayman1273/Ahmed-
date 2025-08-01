<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\News;

class StudentHomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Allow only students
        if ($user->type !== 'student') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only students can access this page.'
            ], 403);
        }

        // Get latest 3 events
        $events = Event::latest()->take(3)->get()->map(function ($event) {
            return [
                'id'          => $event->id,
                'title'       => $event->title,
                'description' => $event->description,
                'image'       => $event->image ? url('/storage/' . ltrim($event->image, '/')) : null,
                'start_time'  => $event->start_time,
            ];
        });

        // Get latest 2 news
        $news = News::latest()->take(2)->get()->map(function ($news) {
            return [
                'id'         => $news->id,
                'title'      => $news->title,
                'content'    => $news->content,
                'image'      => $news->image ? url('/storage/' . ltrim($news->image, '/')) : null,
                'created_at' => $news->created_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'events' => $events,
            'news'   => $news,
        ]);
    }
}
