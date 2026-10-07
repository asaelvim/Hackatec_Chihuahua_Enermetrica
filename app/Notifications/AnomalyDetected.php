<?php

namespace App\Notifications;

use App\Models\Anomaly;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnomalyDetected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Anomaly $anomaly) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', FcmChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $device = $this->anomaly->device;

        return (new MailMessage)
            ->subject('Enermetrica: consumo anómalo detectado')
            ->greeting('Se detectó un consumo fuera de lo normal')
            ->line("Dispositivo: {$device->name}")
            ->line('Valor registrado: '.number_format((float) $this->anomaly->value, 2).' W')
            ->line('Desviación (z-score): '.number_format((float) $this->anomaly->z_score, 2))
            ->action('Ver anomalías', url('/anomalias'))
            ->line('Revisa el dashboard para marcarla como revisada.');
    }

    /**
     * @return array{title: string, body: string, data: array<string, string>}
     */
    public function toFcm(object $notifiable): array
    {
        $device = $this->anomaly->device;

        return [
            'title' => 'Consumo anómalo detectado',
            'body' => "{$device->name}: ".number_format((float) $this->anomaly->value, 2).' W',
            'data' => [
                'type' => 'anomaly',
                'anomaly_id' => (string) $this->anomaly->id,
                'device_id' => (string) $device->id,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'anomaly_id' => $this->anomaly->id,
            'device_id' => $this->anomaly->device_id,
            'value' => $this->anomaly->value,
            'z_score' => $this->anomaly->z_score,
        ];
    }
}
