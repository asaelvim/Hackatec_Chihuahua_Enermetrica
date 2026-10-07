<?php

namespace App\Notifications;

use App\Models\Device;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeviceWentOffline extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Device $device) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', FcmChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Enermetrica: dispositivo sin conexión')
            ->greeting('Un dispositivo dejó de reportar lecturas')
            ->line("Dispositivo: {$this->device->name}")
            ->line('Se marcó como "sin conexión" por falta de lecturas recientes.')
            ->action('Ver dispositivos', url('/dispositivos'));
    }

    /**
     * @return array{title: string, body: string, data: array<string, string>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Dispositivo sin conexión',
            'body' => "{$this->device->name} dejó de reportar lecturas.",
            'data' => [
                'type' => 'device_offline',
                'device_id' => (string) $this->device->id,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'device_id' => $this->device->id,
            'device_name' => $this->device->name,
        ];
    }
}
