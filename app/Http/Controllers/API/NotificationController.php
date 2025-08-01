<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{


public function feed(Request $request)
{
    $user = $request->user();

    $notifications = $user->unreadNotifications
        ->sortByDesc('created_at')
        ->values()
        ->map(function ($noti) {
            return [
                'id'         => $noti->id,
                'title'      => $noti->data['title'] ?? null,
                'message'    => $noti->data['message'] ?? null,
                'read'       => false,
                'created_at' => $noti->created_at,
            ];
        });

    return response()->json([
        'notifications' => $notifications,
        'only_unread'   => true,
    ]);
}







}
