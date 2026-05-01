<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeavesAnomalyNotification extends Notification
{
    use Queueable;

    public function __construct(protected array $payload, protected array $channels = ['mail', 'database', 'broadcast'])
    {
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = ($this->payload['category'] ?? null) === 'leave_anomaly_resolution'
            ? 'Update resolusi anomali cuti: '.$this->payload['employee_name']
            : 'Peringatan anomali cuti: '.$this->payload['anomaly_type_label'];

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Halo '.$notifiable->name.',')
            ->line(($this->payload['category'] ?? null) === 'leave_anomaly_resolution'
                ? 'Terdapat pembaruan resolusi anomali cuti pada tenant Anda.'
                : 'Sistem mendeteksi anomali cuti yang perlu tindak lanjut segera.')
            ->line('Karyawan: '.$this->payload['employee_name'])
            ->line('Jenis anomali: '.$this->payload['anomaly_type_label'])
            ->line('Detail: '.$this->payload['description'])
            ->when(($this->payload['resolution_status'] ?? null) === 'resolved', function (MailMessage $message) {
                return $message
                    ->line('Status: Resolved')
                    ->line('Tindakan: '.($this->payload['resolution_action'] ?? '-'))
                    ->line('Catatan resolusi: '.($this->payload['resolution_note'] ?? '-'));
            })
            ->line('Waktu deteksi: '.$this->payload['detected_at_label'])
            ->action('Buka Dashboard Anomali', $this->payload['dashboard_url'])
            ->line('Silakan review dan tindak lanjuti bila diperlukan.');
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload);
    }

    public function broadcastType(): string
    {
        return 'leave.anomaly.detected';
    }
}