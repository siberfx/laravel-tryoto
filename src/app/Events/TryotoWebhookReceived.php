<?php

namespace Siberfx\LaravelTryoto\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched for every authenticated webhook call OTO sends to the tryoto.callback route.
 */
class TryotoWebhookReceived
{
    use Dispatchable;

    /**
     * @param  array  $payload  the raw webhook body (orderId, status, trackingNumber, ...)
     */
    public function __construct(public array $payload)
    {
    }
}
