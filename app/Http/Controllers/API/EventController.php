<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Notifications\NewsOrEventCreated;


class EventController extends Controller
{
   // Helper to convert image path to full URL
private function getImageUrl($path)
{
    return $path ? config('app.url') . '/storage/' . ltrim($path, '/') : null;
}


    // Get all events
    public function index()
    {
        $events = Event::orderBy('start_time', 'desc')->get();

        $formatted = $events->map(function ($event) {
            return [
                'id'          => $event->id,
                'title'       => $event->title,
                'description' => $event->description,
                'start_time'  => $event->start_time,
                'created_at'  => $event->created_at,
                'image'       => $this->getImageUrl($event->image),
            ];
        });

        return response()->json($formatted);
    }

    // Create a new event (admin only)
    public function store(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized. Token is missing or invalid.'
        ], 401);
    }

    if ($user->type !== 'admin') {
        return response()->json([
            'status' => 'error',
            'message' => 'Forbidden. Admins only.'
        ], 403);
    }

    $request->validate([
        'title'       => 'required|string|max:255',
        'description' => 'required|string',
        'start_time'  => 'required|date',
        'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    $imagePath = $request->hasFile('image')
        ? $request->file('image')->store('events', 'public')
        : null;

    $event = Event::create([
        'title'       => $request->title,
        'description' => $request->description,
        'start_time'  => $request->start_time,
        'image'       => $imagePath,
    ]);

    //  إرسال إشعارات للطلاب والخريجين
    $targets = User::whereIn('type', ['student', 'graduate'])->get();

    foreach ($targets as $target) {
        $target->notify(new NewsOrEventCreated([
            'title' => 'New Event Created',
            'message' => "An event titled '{$event->title}' has been added.",
        ]));
    }

    return response()->json([
        'id'          => $event->id,
        'title'       => $event->title,
        'description' => $event->description,
        'start_time'  => $event->start_time,
        'image'       => $this->getImageUrl($event->image),
    ], 201);
}


    // Update event (admin only)

public function update(Request $request, $id)
{

    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Unauthorized.'], 403);
    }


    $event = Event::find($id);
    if (!$event) {
        return response()->json(['message' => 'Event not found.'], 404);
    }

    $fieldStatus = [];

    if ($request->has('title')) {
        $event->title = $request->input('title');
    } else {
        $fieldStatus['title'] = 'missing';
    }

    if ($request->has('description')) {
        $event->description = $request->input('description');
    } else {
        $fieldStatus['description'] = 'missing';
    }

    if ($request->has('start_time')) {
        $event->start_time = $request->input('start_time');
    } else {
        $fieldStatus['start_time'] = 'missing';
    }

    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('events', 'public');
        $event->image = $path;
    } else {
        $fieldStatus['image'] = 'missing';
    }

    $event->save();

    return response()->json([
        'message' => 'Event updated successfully',
        'field_status' => $fieldStatus,
        'event' => [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'start_time' => $event->start_time,
            'created_at' => $event->created_at,
            'updated_at' => $event->updated_at,
            'image' => $this->getImageUrl($event->image),
        ],
    ]);
}









    // Delete event (admin only)
  public function destroy($id)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized. Token is missing or invalid.'
        ], 401);
    }

    if ($user->type !== 'admin') {
        return response()->json([
            'status' => 'error',
            'message' => 'Forbidden. Admins only.'
        ], 403);
    }

    $event = Event::findOrFail($id);

    if ($event->image) {
        Storage::disk('public')->delete($event->image);
    }

    $event->delete();

    $events = Event::latest()->get()->map(function ($event) {
        return [
            'id'          => $event->id,
            'title'       => $event->title,
            'description' => $event->description,
            'start_time'  => $event->start_time,
            'created_at'  => $event->created_at,
            'image'       => $this->getImageUrl($event->image),
        ];
    });

    return response()->json([
        'message' => 'Event deleted',
        'events'  => $events
    ]);
}


    // Show single event by ID
    public function show($id)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found.'
            ], 404);
        }

        return response()->json([
            'id'          => $event->id,
            'title'       => $event->title,
            'description' => $event->description,
            'start_time'  => $event->start_time,
            'created_at'  => $event->created_at,
            'image'       => $this->getImageUrl($event->image),
        ]);
    }
}
