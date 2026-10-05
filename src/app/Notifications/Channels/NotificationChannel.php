<?php

namespace Siberfx\LaravelTryoto\app\Notifications\Channels;

use Siberfx\LaravelTryoto\app\Notifications\TryotoMessage;

interface NotificationChannel
{
    /**
     * Whether the channel has the credentials it needs. Unconfigured channels are skipped.
     */
    public function isConfigured(): bool;

    /**
     * Deliver the message. Throw on failure; the notifier reports the exception.
     */
    public function send(TryotoMessage $message): void;
}
