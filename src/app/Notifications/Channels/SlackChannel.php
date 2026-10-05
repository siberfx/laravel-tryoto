<?php

namespace Siberfx\LaravelTryoto\app\Notifications\Channels;

use Illuminate\Support\Facades\Http;
use Siberfx\LaravelTryoto\app\Notifications\TryotoMessage;

/**
 * Posts messages to a Slack incoming webhook (https://api.slack.com/messaging/webhooks).
 */
class SlackChannel implements NotificationChannel
{
    public function __construct(protected array $config = [])
    {
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['webhook_url']);
    }

    public function send(TryotoMessage $message): void
    {
        $text = $this->format($message);

        $payload = array_filter([
            'text' => $text,
            'blocks' => [
                ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => $text]],
            ],
            'channel' => $this->config['channel'] ?? null,
            'username' => $this->config['username'] ?? null,
        ]);

        Http::timeout(10)->post($this->config['webhook_url'], $payload)->throw();
    }

    public function format(TryotoMessage $message): string
    {
        $lines = ['*' . self::escape($message->title) . '*'];

        foreach ($message->fields as $label => $value) {
            $lines[] = '*' . self::escape($label) . ':* ' . self::escape($value);
        }

        if ($message->url !== null) {
            $lines[] = '<' . $message->url . '|' . self::escape($message->urlLabel) . '>';
        }

        return implode("\n", $lines);
    }

    /**
     * Slack only requires &, < and > to be escaped in mrkdwn text.
     */
    protected static function escape(string $text): string
    {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $text);
    }
}
