<?php

use Illuminate\Support\Facades\Event;
use Siberfx\LaravelTryoto\app\Events\TryotoWebhookReceived;
use Siberfx\LaravelTryoto\app\Services\TryotoService;

function signedPayload(string $secret = 'secret', array $overrides = []): array
{
    $payload = array_merge([
        'orderId' => '1234',
        'status' => 'delivered',
        'timestamp' => '1595941360328',
        'trackingNumber' => 'ASD00123',
    ], $overrides);

    $payload['signature'] = base64_encode(hash_hmac(
        'sha256',
        "{$payload['orderId']}:{$payload['status']}:{$payload['timestamp']}",
        $secret,
        true,
    ));

    return $payload;
}

describe('registering webhooks', function () {
    it('registers the package callback route by default', function () {
        $this->fakeOto(['success' => true, 'id' => '59']);

        $result = $this->oto()->setWebhook();

        $request = lastApiRequest();
        expect($request->method())->toBe('POST')
            ->and($request->url())->toBe('https://api.tryoto.com/rest/v2/webhook')
            ->and($request->header('Authorization'))->toBe(['Bearer access-token'])
            ->and(jsonBody($request))->toBe([
                'method' => 'post',
                'url' => route('tryoto.callback'),
                'orderPrefix' => '',
                'timestampFormat' => 'yyyy-MM-dd HH:mm:ss',
                'secretKey' => '',
                'authorizationKey' => '',
                'webhookType' => 'orderStatus',
            ])
            ->and($result)->toBe(['success' => true, 'id' => '59']);
    });

    it('reads its defaults from config', function () {
        config(['laravel-tryoto.tryoto.webhook' => [
            'url' => 'https://shop.test/oto',
            'type' => 'shipmentError',
            'secret_key' => 'sec',
            'authorization_key' => 'auth',
            'timestamp_format' => 'yyyy-MM-dd',
            'order_prefix' => 'shop-',
        ]]);
        $this->fakeOto();

        $this->oto()->setWebhook();

        expect(jsonBody(lastApiRequest()))->toMatchArray([
            'url' => 'https://shop.test/oto',
            'webhookType' => 'shipmentError',
            'secretKey' => 'sec',
            'authorizationKey' => 'auth',
            'timestampFormat' => 'yyyy-MM-dd',
            'orderPrefix' => 'shop-',
        ]);
    });

    it('accepts overrides', function () {
        $this->fakeOto();

        $this->oto()->setWebhook(['webhookType' => 'walletTransaction', 'method' => 'put']);

        expect(jsonBody(lastApiRequest()))->toMatchArray(['webhookType' => 'walletTransaction', 'method' => 'put']);
    });

    it('updates a webhook with the required fields filled in', function () {
        $this->fakeOto();

        $this->oto()->updateWebhook(59, ['webhookType' => 'newOrders', 'id' => 'ignored']);

        $request = lastApiRequest();
        expect($request->method())->toBe('PUT')
            ->and(jsonBody($request))->toMatchArray([
                'id' => 59,
                'method' => 'post',
                'url' => route('tryoto.callback'),
                'webhookType' => 'newOrders',
            ]);
    });
});

describe('signature verification', function () {
    it('accepts a valid HmacSHA256 signature', function () {
        expect(TryotoService::verifyWebhookSignature(signedPayload('secret'), 'secret'))->toBeTrue();
    });

    it('rejects invalid signatures', function (array $payload, string $secret) {
        expect(TryotoService::verifyWebhookSignature($payload, $secret))->toBeFalse();
    })->with([
        'wrong secret' => [fn () => signedPayload('other'), 'secret'],
        'tampered status' => [fn () => array_merge(signedPayload('secret'), ['status' => 'returned']), 'secret'],
        'missing signature' => [fn () => array_diff_key(signedPayload('secret'), ['signature' => true]), 'secret'],
        'non string signature' => [fn () => array_merge(signedPayload('secret'), ['signature' => ['x']]), 'secret'],
        'empty secret' => [fn () => signedPayload(''), ''],
    ]);

    it('signs wallet transactions with transactionStatus', function () {
        $payload = ['orderId' => 'OID-1', 'transactionStatus' => 'completed', 'timestamp' => 1767011253000];
        $payload['signature'] = base64_encode(hash_hmac('sha256', 'OID-1:completed:1767011253000', 'secret', true));

        expect(TryotoService::verifyWebhookSignature($payload, 'secret'))->toBeTrue();
    });

    it('signs new orders with the nested order id and status', function () {
        $payload = ['order' => ['incrementId' => 'OID-9', 'status' => 'assignedToWarehouse'], 'timestamp' => 1742822768001];
        $payload['signature'] = base64_encode(hash_hmac('sha256', 'OID-9:assignedToWarehouse:1742822768001', 'secret', true));

        expect(TryotoService::verifyWebhookSignature($payload, 'secret'))->toBeTrue();
    });

    it('signs payloads without a status (shipmentError)', function () {
        $payload = signedPayload('secret', ['status' => '']);
        unset($payload['status']);

        expect(TryotoService::verifyWebhookSignature($payload, 'secret'))->toBeTrue();
    });
});

describe('receiving webhooks', function () {
    beforeEach(fn () => Event::fake([TryotoWebhookReceived::class]));

    it('dispatches an event for accepted calls', function (string $method) {
        $this->json($method, '/tryoto/webhook/callback', signedPayload())
            ->assertOk()
            ->assertExactJson(['success' => true]);

        Event::assertDispatched(TryotoWebhookReceived::class, fn (TryotoWebhookReceived $event) => $event->payload['orderId'] === '1234'
            && $event->payload['trackingNumber'] === 'ASD00123');
    })->with(['POST', 'PUT']);

    it('rejects empty payloads', function () {
        $this->postJson('/tryoto/webhook/callback', [])->assertStatus(422);

        Event::assertNotDispatched(TryotoWebhookReceived::class);
    });

    it('requires the configured authorization key', function (array $headers, int $status) {
        config(['laravel-tryoto.tryoto.webhook.authorization_key' => 'auth-key']);

        $this->postJson('/tryoto/webhook/callback', signedPayload(), $headers)->assertStatus($status);
    })->with([
        'missing header' => [[], 401],
        'wrong key' => [['Authorization' => 'nope'], 401],
        'raw key' => [['Authorization' => 'auth-key'], 200],
        'bearer key' => [['Authorization' => 'Bearer auth-key'], 200],
    ]);

    it('verifies the signature when enabled', function () {
        config([
            'laravel-tryoto.tryoto.webhook.secret_key' => 'secret',
            'laravel-tryoto.tryoto.webhook.verify_signature' => true,
        ]);

        $this->postJson('/tryoto/webhook/callback', signedPayload('wrong'))->assertStatus(401);
        $this->postJson('/tryoto/webhook/callback', signedPayload('secret'))->assertOk();

        Event::assertDispatchedTimes(TryotoWebhookReceived::class, 1);
    });

    it('ignores the signature when verification is disabled', function () {
        config(['laravel-tryoto.tryoto.webhook.secret_key' => 'secret']);

        $this->postJson('/tryoto/webhook/callback', signedPayload('wrong'))->assertOk();
    });
});
