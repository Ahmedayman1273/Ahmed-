<?php

namespace App\Notifications;
// app/Notifications/RequestStatusUpdated.php

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestStatusUpdated extends Notification
{
    use Queueable;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database']; // علشان تتخزن في جدول notifications
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->data['title'],
            'message' => $this->data['message'],
        ];
    }
}
