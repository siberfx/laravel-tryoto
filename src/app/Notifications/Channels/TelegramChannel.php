<?php

namespace Siberfx\LaravelTryoto\app\Notifications\Channels;

use Illuminate\Support\Facades\Http;
use Siberfx\LaravelTryoto\app\Notifications\TryotoMessage;

/**
 * Sends messages through the Telegram Bot API (https://core.telegram.org/bots/api#sendmessage).
 */
class TelegramChannel implements NotificationChannel
{
    public function __construct(protected array $config = [])
    {
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['bot_token']) && !empty($this->config['chat_id']);
    }

    public function send(TryotoMessage $message): void
    {
        $apiUrl = rtrim($this->config['api_url'] ?? 'https://api.telegram.org', '/');

        $payload = array_filter([
            'chat_id' => $this->config['chat_id'],
            'message_thread_id' => $this->config['thread_id'] ?? null,
            'text' => $this->format($message),
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
        ], static fn ($value) => $value !== null && $value !== '');

        Http::timeout(10)
            ->post("{$apiUrl}/bot{$this->config['bot_token']}/sendMessage", $payload)
            ->throw();
    }

    public function format(TryotoMessage $message): string
    {
        $lines = ['<b>' . self::escape($message->title) . '</b>'];

        foreach ($message->fields as $label => $value) {
            $lines[] = '<b>' . self::escape($label) . ':</b> ' . self::escape($value);
        }

        if ($message->url !== null) {
            $lines[] = '<a href="' . self::escape($message->url) . '">' . self::escape($message->urlLabel) . '</a>';
        }

        return implode("\n", $lines);
    }

    protected static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
