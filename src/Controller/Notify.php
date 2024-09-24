<?php

namespace App\Controller;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Service\Logger;

class Notify
{
    private $messaging;

    public function __construct($messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendNotificationToUser($logger, $fcm_token, $title, $body, $data)
    {
        $notification = [
            'title' => $title,
            'body' => $body
        ];
        $notification = Notification::fromArray($notification);

        $logger->log(["message" => "Notification succesfully send to", "notification" => $notification], 'INFO');

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
