<?php

namespace Siberfx\LaravelTryoto\app\Notifications;

use Siberfx\LaravelTryoto\app\Events\TryotoWebhookReceived;
use Siberfx\LaravelTryoto\app\Jobs\SendTryotoNotification;
use Siberfx\LaravelTryoto\app\Notifications\Channels\NotificationChannel;
use Siberfx\LaravelTryoto\app\Notifications\Channels\MailChannel;
use Siberfx\LaravelTryoto\app\Notifications\Channels\SlackChannel;
use Siberfx\LaravelTryoto\app\Notifications\Channels\TelegramChannel;
use Throwable;

/**
 * Sends TryotoMessages to every configured channel (Slack, Telegram, mail, or custom ones).
 *
 * Channels are read from the "notifications" config section on each send; a channel whose
 * credentials are null is skipped, so nothing is sent until you opt in.
 */
class TryotoNotifier
{
    /** @var array<string, NotificationChannel> */
    protected array $extensions = [];

    /**
     * Register an additional channel, or replace a built-in one by using its name.
     */
    public function extend(string $name, NotificationChannel $channel): static
    {
        $this->extensions[$name] = $channel;

        return $this;
    }

    /**
     * The channels that are configured and will receive messages.
     *
     * @return array<string, NotificationChannel>
     */
    public function channels(): array
    {
        $config = $this->config();

        $channels = array_merge([
            'slack' => new SlackChannel((array) ($config['slack'] ?? [])),
            'telegram' => new TelegramChannel((array) ($config['telegram'] ?? [])),
            'mail' => new MailChannel((array) ($config['mail'] ?? [])),
        ], $this->extensions);

        return array_filter($channels, static fn (NotificationChannel $channel) => $channel->isConfigured());
    }

    public function enabled(): bool
    {
        return $this->channels() !== [];
    }

    /**
     * Deliver a message to every configured channel. A failing channel is reported and does not
     * stop the others.
     *
     * @return list<string> names of the channels that accepted the message
     */
    public function send(TryotoMessage $message): array
    {
        $sent = [];

        foreach ($this->channels() as $name => $channel) {
            try {
                $channel->send($message);
                $sent[] = $name;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $sent;
    }

    /**
     * Notify about an incoming webhook payload, honouring the type/status filters and the queue setting.
     */
    public function notifyWebhook(array $payload): void
    {
        if (!$this->shouldNotify($payload) || !$this->enabled()) {
            return;
        }

        $message = TryotoMessage::fromWebhook($payload);
        $config = $this->config();

        if (empty($config['queue']) && empty($config['queue_connection'])) {
            $this->send($message);

            return;
        }

        $job = new SendTryotoNotification($message);

        if (!empty($config['queue_connection'])) {
            $job->onConnection($config['queue_connection']);
        }

        if (!empty($config['queue'])) {
            $job->onQueue($config['queue']);
        }

        dispatch($job);
    }

    public function shouldNotify(array $payload): bool
    {
        $config = $this->config();
        $type = TryotoWebhookReceived::detectType($payload);

        $types = $config['types'] ?? null;
        if (is_array($types) && !in_array($type, $types, true)) {
            return false;
        }

        $statuses = $config['statuses'] ?? null;
        if ($type === TryotoWebhookReceived::ORDER_STATUS && is_array($statuses)) {
            $status = strtolower((string) ($payload['status'] ?? ''));

            return in_array($status, array_map('strtolower', $statuses), true);
        }

        return true;
    }

    protected function config(): array
    {
        return (array) config('laravel-tryoto.tryoto.notifications', []);
    }
}
