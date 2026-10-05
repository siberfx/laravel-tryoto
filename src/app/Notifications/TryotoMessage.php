<?php

namespace Siberfx\LaravelTryoto\app\Notifications;

use Siberfx\LaravelTryoto\app\Events\TryotoWebhookReceived;

/**
 * Channel-agnostic chat message: a title, labeled fields and an optional link.
 * Each channel renders it in its own markup.
 *
 * Webhook messages are translated with the "tryoto::notifications" language lines, in the
 * locale set by "notifications.locale" (English by default, Turkish included).
 */
class TryotoMessage
{
    /**
     * @param  array<string, string>  $fields
     */
    public function __construct(
        public string $title,
        public array $fields = [],
        public ?string $url = null,
        public ?string $urlLabel = null,
        public ?string $type = null,
        public ?string $locale = null,
    ) {
        $this->locale ??= self::defaultLocale();
        $this->urlLabel ??= self::trans('links.open', [], $this->locale);
    }

    public static function make(string $title, ?string $locale = null): self
    {
        return new self($title, locale: $locale);
    }

    /**
     * Add a labelled line. Empty values are skipped so optional payload fields can be passed as-is.
     */
    public function field(string $label, mixed $value): self
    {
        if ($value === null || $value === '' || is_array($value)) {
            return $this;
        }

        $this->fields[$label] = is_bool($value)
            ? self::trans($value ? 'boolean.yes' : 'boolean.no', [], $this->locale)
            : (string) $value;

        return $this;
    }

    public function link(?string $url, ?string $label = null): self
    {
        if ($url !== null && $url !== '') {
            $this->url = $url;
            $this->urlLabel = $label ?? self::trans('links.open', [], $this->locale);
        }

        return $this;
    }

    /**
     * Build a message describing an OTO webhook payload.
     *
     * @param  string|null  $locale  defaults to the "notifications.locale" config value
     */
    public static function fromWebhook(array $payload, ?string $locale = null): self
    {
        $locale ??= self::defaultLocale();
        $type = TryotoWebhookReceived::detectType($payload);

        $message = match ($type) {
            TryotoWebhookReceived::SHIPMENT_ERROR => self::shipmentError($payload, $locale),
            TryotoWebhookReceived::NEW_ORDERS => self::newOrder($payload['order'], $locale),
            TryotoWebhookReceived::WALLET_TRANSACTION => self::walletTransaction($payload, $locale),
            default => self::orderStatus($payload, $locale),
        };

        $message->type = $type;

        return $message;
    }

    /**
     * Translate a "tryoto::notifications" line, falling back to English for locales without
     * translations.
     */
    public static function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale ??= self::defaultLocale();
        $fullKey = "tryoto::notifications.{$key}";
        $translator = app('translator');

        foreach ([$locale, 'en'] as $candidate) {
            $line = $translator->get($fullKey, $replace, $candidate, false);

            if (is_string($line) && $line !== $fullKey) {
                return $line;
            }
        }

        return $key;
    }

    /**
     * Human readable status name, or the raw OTO code when it has no translation.
     */
    public static function statusLabel(string $status, ?string $locale = null): string
    {
        $label = self::trans("statuses.{$status}", [], $locale);

        return $label === "statuses.{$status}" ? $status : $label;
    }

    public static function defaultLocale(): string
    {
        return (string) (config('laravel-tryoto.tryoto.notifications.locale') ?: 'en');
    }

    protected static function orderStatus(array $payload, string $locale): self
    {
        $status = (string) ($payload['status'] ?? 'updated');
        $label = fn (string $key) => self::trans("fields.{$key}", [], $locale);

        $icon = match (strtolower($status)) {
            'delivered' => '✅',
            'returned', 'reversereturned' => '↩️',
            'canceled', 'cancelled', 'shipmentcanceled' => '❌',
            'outfordelivery', 'out_for_delivery' => '🚚',
            default => '📦',
        };

        $title = self::trans('titles.order_status', [
            'icon' => $icon,
            'order' => $payload['orderId'] ?? '',
            'status' => self::statusLabel($status, $locale),
        ], $locale);

        return self::make($title, $locale)
            ->field($label('status'), $status)
            ->field($label('carrier_status'), $payload['dcStatus'] ?? null)
            ->field($label('return_status'), $payload['returnStatus'] ?? null)
            ->field($label('carrier'), $payload['deliveryCompany'] ?? null)
            ->field($label('tracking_number'), $payload['trackingNumber'] ?? null)
            ->field($label('driver'), trim(($payload['driverName'] ?? '') . ' ' . ($payload['driverPhone'] ?? '')))
            ->field($label('failed_attempt'), $payload['attemptFailureReason'] ?? null)
            ->field($label('note'), $payload['note'] ?? null)
            ->link(
                $payload['brandedTrackingURL'] ?? $payload['trackingUrl'] ?? null,
                self::trans('links.track_shipment', [], $locale),
            );
    }

    protected static function shipmentError(array $payload, string $locale): self
    {
        $label = fn (string $key) => self::trans("fields.{$key}", [], $locale);

        return self::make(self::trans('titles.shipment_error', ['order' => $payload['orderId'] ?? ''], $locale), $locale)
            ->field($label('error'), $payload['errorMessage'] ?? null)
            ->field($label('code'), $payload['errorCode'] ?? null)
            ->field($label('carrier'), $payload['deliveryCompany'] ?? null)
            ->field($label('carrier_response'), $payload['deliveryCompanyResponse'] ?? null);
    }

    protected static function newOrder(array $order, string $locale): self
    {
        $label = fn (string $key) => self::trans("fields.{$key}", [], $locale);
        $address = is_array($order['address'] ?? null) ? $order['address'] : [];
        $total = isset($order['grandTotal']) ? trim($order['grandTotal'] . ' ' . ($order['currency'] ?? '')) : null;

        $title = self::trans('titles.new_order', ['order' => $order['incrementId'] ?? $order['otoId'] ?? ''], $locale);

        return self::make(trim($title), $locale)
            ->field($label('status'), $order['status'] ?? null)
            ->field($label('total'), $total)
            ->field($label('payment'), $order['paymentMethod'] ?? null)
            ->field($label('customer'), trim(($address['name'] ?? '') . ', ' . ($address['city'] ?? ''), ', '))
            ->field($label('sales_channel'), $order['salesChannel'] ?? null)
            ->field($label('brand'), $order['brand'] ?? null)
            ->field($label('items'), is_array($order['items'] ?? null) ? count($order['items']) : null);
    }

    protected static function walletTransaction(array $payload, string $locale): self
    {
        $label = fn (string $key) => self::trans("fields.{$key}", [], $locale);
        $title = self::trans('titles.wallet_transaction', ['amount' => $payload['amount'] ?? ''], $locale);

        return self::make(trim($title), $locale)
            ->field($label('order'), $payload['orderId'] ?? null)
            ->field($label('type'), $payload['transactionType'] ?? null)
            ->field($label('charge_type'), $payload['chargeType'] ?? null)
            ->field($label('status'), $payload['transactionStatus'] ?? null)
            ->field($label('description'), $payload['description'] ?? null)
            ->field($label('remaining_balance'), $payload['remainingAmount'] ?? null)
            ->field($label('carrier'), $payload['deliveryCompanyName'] ?? null);
    }
}
