<?php

namespace App\Controller;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class Notify
{
    private $messaging;

    public function __construct($messaging)
    {
        $this->messaging = $messaging;
    }
 
    public function sendNotificationToUser($fcm_token, $title, $body, $data)
    {
        $notification = Notification::fromArray([
            'title' => $title,
            'body' => $body
        ]);
        
        $message = CloudMessage::withTarget('token', $fcm_token)
            ->withNotification($notification);

        $message = $message->withData($data);

        $this->messaging->send($message);
    }
 
    public function sendMessage($token, $report)
    {
        $message = CloudMessage::fromArray([
            'token' => $token,
            'data' => $report,
        ]);
    
        $this->messaging->send($message);
    }
}
