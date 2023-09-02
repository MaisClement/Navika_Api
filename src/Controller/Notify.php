<?php

namespace App\Controller;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class Notify
{
    public function __construct($messaging)
    {
        $this->messaging = $messaging;
    }
 
    public function sendNotification($fcm_token, $title, $body)
    {
        $notification = Notification::fromArray([
            'title' => $title,
            'body' => $body
        ]);
        
        $message = CloudMessage::withTarget('token', $fcm_token)
            ->withNotification($notification);

        $this->messaging->send($message);

        return true;
    }
}
