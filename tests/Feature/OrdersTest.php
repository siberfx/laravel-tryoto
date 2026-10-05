<?php

use Illuminate\Support\Facades\Http;

it('lists orders with pagination and drops empty filters', function () {
    $this->fakeOto(['orders' => [['orderId' => '1']]]);

    $result = $this->oto()->listOrders(2, ['status' => 'delivered', 'minDate' => '', 'maxDate' => null]);

    expect(lastApiRequest()->url())->toBe('https://api.tryoto.com/rest/v2/orders?perPage=100&page=2&status=delivered')
        ->and($result)->toBeObject()
        ->and($result->orders[0]->orderId)->toBe('1');
});

it('lets filters override the page size', function () {
    $this->fakeOto();

    $this->oto()->listOrders(1, ['perPage' => 10]);

    expect(lastApiRequest()->url())->toBe('https://api.tryoto.com/rest/v2/orders?perPage=10&page=1');
});

it('returns order details as an object', function () {
    $this->fakeOto(['orderId' => 'OID-1', 'status' => 'delivered']);

    $result = $this->oto()->orderDetail('OID-1');

    expect(lastApiRequest()->url())->toBe('https://api.tryoto.com/rest/v2/orderDetails?orderId=OID-1')
        ->and($result->status)->toBe('delivered');
});

it('skips the request when no order id is given', function (string $method, array $arguments) {
    $this->fakeOto();

    expect($this->oto()->{$method}(...$arguments))->toBe([]);

    Http::assertNothingSent();
})->with([
    'orderDetail' => ['orderDetail', [null]],
    'cancelOrder' => ['cancelOrder', ['']],
    'holdOrder' => ['holdOrder', [null]],
    'unHoldOrder' => ['unHoldOrder', ['']],
    'updateOrderStatus without ids' => ['updateOrderStatus', [[], 'delivered']],
    'updateOrderStatus without status' => ['updateOrderStatus', [['1'], '']],
]);

it('returns OTO error bodies to the caller', function () {
    $this->fakeOto(routes: [
        '*/rest/v2/cancelOrder' => Http::response(['success' => false, 'otoErrorCode' => 'OTO1002', 'otoErrorMessage' => 'The order ID does not exist'], 404),
    ]);

    expect($this->oto()->cancelOrder('missing'))->toBe([
        'success' => false,
        'otoErrorCode' => 'OTO1002',
        'otoErrorMessage' => 'The order ID does not exist',
    ]);
});

it('can call any endpoint through call() and request()', function () {
    $this->fakeOto(['brands' => []]);

    expect($this->oto()->call('GET', 'rest/v2/getBrandList', query: ['page' => 1]))->toBe(['brands' => []])
        ->and(lastApiRequest()->url())->toBe('https://api.tryoto.com/rest/v2/getBrandList?page=1');

    $response = $this->oto()->request('post', '/rest/v2/assignDriver', ['orderIDs' => ['1'], 'driverID' => 5]);

    expect($response->successful())->toBeTrue()
        ->and(lastApiRequest()->method())->toBe('POST')
        ->and(jsonBody(lastApiRequest()))->toBe(['orderIDs' => ['1'], 'driverID' => 5]);
});
