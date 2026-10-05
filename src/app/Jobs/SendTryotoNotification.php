<?php

namespace Siberfx\LaravelTryoto\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Siberfx\LaravelTryoto\app\Notifications\TryotoMessage;
use Siberfx\LaravelTryoto\app\Notifications\TryotoNotifier;

/**
 * Delivers a notification in the background when "notifications.queue" is configured.
 */
class SendTryotoNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public TryotoMessage $message)
    {
    }

    public function handle(TryotoNotifier $notifier): void
    {
        $notifier->send($this->message);
    }
}
