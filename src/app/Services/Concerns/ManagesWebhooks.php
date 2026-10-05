<?php

namespace Siberfx\LaravelTryoto\app\Services\Concerns;

/**
 * "Webhook" section of the OTO API.
 *
 * Webhook types: orderStatus, shipmentError, newOrders, walletTransaction.
 */
trait ManagesWebhooks
{
    /**
     * POST /rest/v2/webhook — register a webhook (max 10 per account).
     *
     * Defaults come from the "webhook" config section; the URL falls back to this package's
     * tryoto.callback route.
     *
     * @param  array  $overrides  method, url, orderPrefix, timestampFormat, secretKey, authorizationKey, webhookType
     */
    public function setWebhook(array $overrides = [])
    {
        return $this->call('POST', '/rest/v2/webhook', array_merge($this->webhookDefaults(), $overrides));
    }


    /**
     * GET /rest/v2/webhook — list registered webhooks, optionally a single one by id.
     */
    public function listWebhooks($id = null)
    {
        return $this->call('GET', '/rest/v2/webhook', query: ['id' => $id]);
    }


    /**
     * PUT /rest/v2/webhook — method and url are required by OTO, so they default from config too.
     */
    public function updateWebhook($id, array $parameters = [])
    {
        return $this->call('PUT', '/rest/v2/webhook', array_merge(
            $this->webhookDefaults(),
            $parameters,
            ['id' => $id],
        ));
    }


    /**
     * DELETE /rest/v2/webhook?id={id}
     */
    public function deleteWebhook($id)
    {
        return $this->call('DELETE', '/rest/v2/webhook', query: ['id' => $id]);
    }


    /**
     * Check an incoming payload's signature: OTO signs "orderId:status:timestamp" with
     * HmacSHA256 using the webhook's secretKey and Base64 encodes the result.
     */
    public static function verifyWebhookSignature(array $payload, string $secretKey): bool
    {
        $signature = $payload['signature'] ?? null;

        if (!is_string($signature) || $signature === '' || $secretKey === '') {
            return false;
        }

        $message = implode(':', [
            $payload['orderId'] ?? '',
            $payload['status'] ?? '',
            $payload['timestamp'] ?? '',
        ]);

        return hash_equals(base64_encode(hash_hmac('sha256', $message, $secretKey, true)), $signature);
    }


    protected function webhookDefaults(): array
    {
        $config = config('laravel-tryoto.tryoto.webhook', []);

        return [
            'method' => 'post',
            'url' => ($config['url'] ?? null) ?: route('tryoto.callback'),
            'orderPrefix' => (string) ($config['order_prefix'] ?? ''),
            'timestampFormat' => (string) ($config['timestamp_format'] ?? 'yyyy-MM-dd HH:mm:ss'),
            'secretKey' => (string) ($config['secret_key'] ?? ''),
            'authorizationKey' => (string) ($config['authorization_key'] ?? ''),
            'webhookType' => (string) ($config['type'] ?? 'orderStatus'),
        ];
    }
}
