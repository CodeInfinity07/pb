<?php
// app/Notifications/GeneralNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class GeneralNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $icon;
    protected $user;

    public function __construct($title, $message, $icon = 'iconamoon:notification-duotone', $user = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->icon = $icon;
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'user' => $this->user ? [
                'name' => $this->user->name,
                'avatar' => $this->user->profile->avatar ?? null
            ] : null,
            'created_at' => now()->format('M d, Y H:i'),
        ];
    }
}