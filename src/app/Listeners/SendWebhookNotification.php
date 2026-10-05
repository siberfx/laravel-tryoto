<?php

namespace Siberfx\LaravelTryoto\app\Listeners;

use Siberfx\LaravelTryoto\app\Events\TryotoWebhookReceived;
use Siberfx\LaravelTryoto\app\Notifications\TryotoNotifier;

/**
 * Forwards accepted OTO webhooks to the configured chat channels.
 */
class SendWebhookNotification
{
    public function __construct(protected TryotoNotifier $notifier)
    {
    }

    public function handle(TryotoWebhookReceived $event): void
    {
        $this->notifier->notifyWebhook($event->payload);
    }
}
