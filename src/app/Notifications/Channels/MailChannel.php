<?php

namespace Siberfx\LaravelTryoto\app\Notifications\Channels;

use Illuminate\Support\Facades\Mail;
use Siberfx\LaravelTryoto\app\Mail\TryotoNotificationMail;
use Siberfx\LaravelTryoto\app\Notifications\TryotoMessage;

/**
 * Emails messages through the application's Laravel mailer.
 */
class MailChannel implements NotificationChannel
{
    public function __construct(protected array $config = [])
    {
    }

    public function isConfigured(): bool
    {
        return $this->recipients() !== [];
    }

    public function send(TryotoMessage $message): void
    {
        Mail::mailer($this->config['mailer'] ?? null)
            ->to($this->recipients())
            ->send(new TryotoNotificationMail(
                $message,
                (string) ($this->config['subject_prefix'] ?? ''),
                $this->config['from_address'] ?? null,
                $this->config['from_name'] ?? null,
            ));
    }

    /**
     * Recipients from "to", given as an array or a comma separated string.
     *
     * @return list<string>
     */
    public function recipients(): array
    {
        $to = $this->config['to'] ?? null;
        $to = is_array($to) ? $to : explode(',', (string) $to);

        return array_values(array_filter(array_map('trim', $to)));
    }
}
