<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Notifications\NewsOrEventCreated;
use App\Libraries\ImageValidator; 

class EventController extends Controller
{
    private function getImageUrl($path)
    {
        return $path ? config('app.url') . '/storage/' . ltrim($path, '/') : null;
    }

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

    // Create a new event
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        if ($user->type !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Admins only.'], 403);
        }

        //  Validate non-image fields
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'start_time'  => 'required|date',
        ]);

        //  Validate image (white list)
        if ($request->hasFile('image')) {
            $validator = ImageValidator::validate($request, 'image');
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $mime = $request->file('image')->getMimeType();
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
                return response()->json(['error' => 'Invalid image type'], 400);
            }

            $imagePath = $request->file('image')->store('events', 'public');
        } else {
            $imagePath = null;
        }

        $event = Event::create([
            'title'       => $request->title,
            'description' => $request->description,
            'start_time'  => $request->start_time,
            'image'       => $imagePath,
        ]);

        // Notify students & graduates
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



 // Update event
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
            // WIHTE LIST
            $validator = ImageValidator::validate($request, 'image');
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $mime = $request->file('image')->getMimeType();
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
                return response()->json(['error' => 'Invalid image type'], 400);
            }

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
                'id'          => $event->id,
                'title'       => $event->title,
                'description' => $event->description,
                'start_time'  => $event->start_time,
                'created_at'  => $event->created_at,
                'updated_at'  => $event->updated_at,
                'image'       => $this->getImageUrl($event->image),
            ],
        ]);
    }

// DELETE
    public function destroy($id)
    {
        $user = auth()->user();

        if (!$user || $user->type !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Admins only.'], 403);
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

    public function show($id)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $event = Event::find($id);

        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Record not found.'], 404);
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
