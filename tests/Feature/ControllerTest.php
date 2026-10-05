<?php

use Siberfx\LaravelTryoto\app\Http\Controllers\Api\TryOtoController;
use Siberfx\LaravelTryoto\app\Services\TryotoService;

it('uses the container bound service', function () {
    expect(app(TryOtoController::class)->service)->toBe(app(TryotoService::class));
});

it('builds and sends a createOrder body from the sample order', function () {
    $this->fakeOto();

    $body = app(TryOtoController::class)->dataSet();

    $request = lastApiRequest();
    expect($request->url())->toBe('https://api.tryoto.com/rest/v2/createOrder')
        ->and(jsonBody($request)['orderId'])->toBe('1231223')
        ->and($body['packageCount'])->toBe(2)
        ->and($body['customer']['country'])->toBe('TR')
        ->and($body['items'])->toHaveCount(2);
});

it('fills product defaults and computes row totals', function () {
    $items = app(TryOtoController::class)->productsParser([
        ['productId' => 1, 'name' => 'Shirt', 'price' => 12.5, 'quantity' => 2, 'sku' => 'S1'],
    ]);

    expect($items)->toBe([[
        'productId' => 1,
        'name' => 'Shirt',
        'price' => 12.5,
        'rowTotal' => 25.0,
        'taxAmount' => 0,
        'quantity' => 2,
        'serialnumber' => '',
        'sku' => 'S1',
        'image' => '',
    ]]);
});

it('exposes a set-webhook route', function () {
    $this->fakeOto(['success' => true, 'id' => '1']);

    $this->get('/tryoto/set-webhook')->assertOk()->assertJson(['success' => true]);
});
