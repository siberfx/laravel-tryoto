<?php

namespace Siberfx\LaravelTryoto\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched for every authenticated webhook call OTO sends to the tryoto.callback route.
 */
class TryotoWebhookReceived
{
    use Dispatchable;

    public const string ORDER_STATUS = 'orderStatus';
    public const string SHIPMENT_ERROR = 'shipmentError';
    public const string NEW_ORDERS = 'newOrders';
    public const string WALLET_TRANSACTION = 'walletTransaction';

    /**
     * @param  array  $payload  the raw webhook body (orderId, status, trackingNumber, ...)
     */
    public function __construct(public array $payload)
    {
    }

    /**
     * The webhook type, detected from the payload shape (OTO does not send it explicitly).
     */
    public function type(): string
    {
        return self::detectType($this->payload);
    }

    public static function detectType(array $payload): string
    {
        return match (true) {
            isset($payload['order']) && is_array($payload['order']) => self::NEW_ORDERS,
            isset($payload['errorCode']) || isset($payload['errorMessage']) => self::SHIPMENT_ERROR,
            isset($payload['transactionStatus']) || isset($payload['transactionType']) => self::WALLET_TRANSACTION,
            default => self::ORDER_STATUS,
        };
    }
}
