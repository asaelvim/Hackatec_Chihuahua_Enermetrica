<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FcmChannel
{
    public function __construct(protected Messaging $messaging) {}

    /**
     * Send the notification as a push message to every FCM token registered
     * by the notifiable (a User with one or more mobile devices linked).
     */
    public function send(object $notifiable, Notification $notification): ?MulticastSendReport
    {
        if (! method_exists($notification, 'toFcm')) {
            return null;
        }

        $tokens = $notifiable->deviceTokens()->pluck('fcm_token')->all();

        if (empty($tokens)) {
            return null;
        }

        $payload = $notification->toFcm($notifiable);

        $message = CloudMessage::new()
            ->withNotification(FcmNotification::create($payload['title'], $payload['body']))
            ->withData($payload['data'] ?? []);

        try {
            $report = $this->messaging->sendMulticast($message, $tokens);
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar la notificación push por FCM.', ['error' => $e->getMessage()]);

            return null;
        }

        $this->pruneInvalidTokens($notifiable, $report);

        return $report;
    }

    /**
     * Remove device tokens that Firebase reports as unregistered or invalid,
     * so stale tokens don't keep failing on every future notification.
     */
    protected function pruneInvalidTokens(object $notifiable, MulticastSendReport $report): void
    {
        $invalidTokens = $report->invalidTokens();

        if (! empty($invalidTokens)) {
            $notifiable->deviceTokens()->whereIn('fcm_token', $invalidTokens)->delete();
        }
    }
}
