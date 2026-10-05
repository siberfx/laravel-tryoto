# Laravel Tryoto

[![Latest Version on Packagist](https://img.shields.io/packagist/v/siberfx/laravel-tryoto.svg?style=flat-square)](https://packagist.org/packages/siberfx/laravel-tryoto)
[![Tests](https://img.shields.io/github/actions/workflow/status/siberfx/laravel-tryoto/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/siberfx/laravel-tryoto/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/siberfx/laravel-tryoto.svg?style=flat-square)](https://packagist.org/packages/siberfx/laravel-tryoto)
[![PHP Version](https://img.shields.io/packagist/dependency-v/siberfx/laravel-tryoto/php.svg?style=flat-square)](https://packagist.org/packages/siberfx/laravel-tryoto)
[![Laravel](https://img.shields.io/badge/laravel-12.x%20%7C%2013.x-FF2D20.svg?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)

A Laravel integration for the [OTO](https://tryoto.com) shipping and fulfillment platform
(tryoto.com), built on Laravel's HTTP client. It wraps the OTO REST API v2: orders, delivery rates,
shipments, AWB and label printing, tracking, returns, pickup locations, products, stock, coverage
and webhooks.

> This is an unofficial, community-maintained package. It is not affiliated with OTO.

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick start](#quick-start)
- [Authentication](#authentication)
- [Usage](#usage)
  - [Orders](#orders)
  - [Shipping prices and shipments](#shipping-prices-and-shipments)
  - [Printing](#printing)
  - [Tracking](#tracking)
  - [Returns](#returns)
  - [Pickup locations](#pickup-locations)
  - [Account and transactions](#account-and-transactions)
  - [Products, boxes and stock](#products-boxes-and-stock)
  - [Coverage, cities and addresses](#coverage-cities-and-addresses)
  - [Any other endpoint](#any-other-endpoint)
- [Webhooks](#webhooks)
- [Error handling](#error-handling)
- [Endpoint reference](#endpoint-reference)
- [Testing your application](#testing-your-application)
- [Testing the package](#testing-the-package)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security](#security)
- [Credits](#credits)
- [License](#license)

## Features

- **Around 70 endpoint wrappers** covering the main sections of the [official OTO API documentation](https://apis.tryoto.com/).
- **Token handling:** the refresh token is exchanged for an access token on first use, the access token is cached, and a `401` triggers one refresh and retry.
- **Sandbox switch:** one env flag moves every call to OTO's staging API, with its own token cache.
- **Webhooks:** register, list, update and delete them from code. The bundled callback route checks the authorization key and signature, then fires a Laravel event.
- **Escape hatch:** `call()` / `request()` reach any endpoint with the same authentication.
- **Tested:** a Pest suite checks each wrapper's HTTP method, path, query and body against the docs. CI runs PHP 8.4/8.5 against Laravel 12/13.

## Requirements

| Package | PHP       | Laravel    |
| ------- | --------- | ---------- |
| 2.x     | 8.4, 8.5  | 12.x, 13.x |
| 1.x     | 8.2, 8.3  | 10.x, 11.x |

## Installation

```bash
composer require siberfx/laravel-tryoto
```

The service provider is registered automatically through package discovery.

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Siberfx\LaravelTryoto\TryotoServiceProvider" --tag=config
```

Optionally publish the routes file to customise or protect the package routes:

```bash
php artisan vendor:publish --provider="Siberfx\LaravelTryoto\TryotoServiceProvider" --tag=routes
```

## Configuration

Add your credentials to `.env`. The refresh token is generated in the OTO dashboard.

```dotenv
TRYOTO_REFRESH_TOKEN=your-live-refresh-token
TRYOTO_SANDBOX=false
```

All options in `config/laravel-tryoto.php`:

| Key                                | Env variable                       | Default                          | Description                                                                 |
| ---------------------------------- | ---------------------------------- | -------------------------------- | --------------------------------------------------------------------------- |
| `tryoto.sandbox`                   | `TRYOTO_SANDBOX`                   | `false`                          | Use the staging API and staging refresh token.                              |
| `tryoto.live.url`                  | `TRYOTO_URL`                       | `https://api.tryoto.com`         | Live API base URL.                                                          |
| `tryoto.live.token`                | `TRYOTO_REFRESH_TOKEN`             |                                  | Live refresh token.                                                         |
| `tryoto.test.url`                  | `TRYOTO_TEST_URL`                  | `https://staging-api.tryoto.com` | Staging API base URL.                                                       |
| `tryoto.test.token`                | `TRYOTO_TEST_REFRESH_TOKEN`        |                                  | Staging refresh token.                                                      |
| `tryoto.cache_name`                | —                                  | `oto_api_token`                  | Cache key for the access token (`_sandbox` is appended in sandbox mode).    |
| `tryoto.cache_time`                | —                                  | `58`                             | Minutes to cache the access token (OTO tokens are valid for 60).            |
| `tryoto.timeout`                   | `TRYOTO_TIMEOUT`                   | `30`                             | HTTP timeout in seconds.                                                    |
| `tryoto.webhook.url`               | `TRYOTO_WEBHOOK_URL`               | `route('tryoto.callback')`       | URL registered by `setWebhook()`.                                           |
| `tryoto.webhook.type`              | `TRYOTO_WEBHOOK_TYPE`              | `orderStatus`                    | `orderStatus`, `shipmentError`, `newOrders` or `walletTransaction`.         |
| `tryoto.webhook.authorization_key` | `TRYOTO_WEBHOOK_AUTHORIZATION_KEY` |                                  | Sent to OTO on registration; required on incoming calls when set.           |
| `tryoto.webhook.secret_key`        | `TRYOTO_WEBHOOK_SECRET`            |                                  | Key OTO uses to sign webhook payloads.                                      |
| `tryoto.webhook.verify_signature`  | `TRYOTO_WEBHOOK_VERIFY_SIGNATURE`  | `false`                          | Reject incoming calls whose signature does not match `secret_key`.          |
| `tryoto.webhook.timestamp_format`  | —                                  | `yyyy-MM-dd HH:mm:ss`            | Timestamp format OTO uses in payloads.                                      |
| `tryoto.webhook.order_prefix`      | —                                  | `''`                             | Prefix OTO strips from order ids before sending the webhook.                |

## Quick start

`TryotoService` is bound as a singleton. Inject it anywhere:

```php
use Siberfx\LaravelTryoto\app\Services\TryotoService;

class ShippingController
{
    public function __construct(private TryotoService $oto)
    {
    }

    public function ship(string $orderId)
    {
        // pick the first delivery option offered for the order...
        $options = $this->oto->getDeliveryFee($orderId);
        $optionId = $options['deliveryCompany'][0]['deliveryOptionId'];

        // ...create the shipment and return the AWB print URL
        $this->oto->createShipment($orderId, $optionId);

        return $this->oto->printAwb($orderId)['printAWBURL'];
    }
}
```

Methods return the decoded JSON response as an array. `listOrders()` and `orderDetail()` return
objects (`stdClass`), as in earlier versions.

## Authentication

OTO access tokens are valid for one hour. On the first API call the service exchanges your refresh
token (`POST /rest/v2/refreshToken`) for an access token and caches it for `cache_time` minutes.
Every request sends it as `Authorization: Bearer <token>`.

- If OTO answers `401`, the token is refreshed and the request is retried **once**.
- If the token exchange itself fails, `Siberfx\LaravelTryoto\app\Exceptions\TryotoException` is thrown.
- `$oto->authorize(fresh: true)` forces a new token.

## Usage

### Orders

```php
$oto->createOrder([
    'orderId' => '1234',
    'pickupLocationCode' => 'jdd_wh',
    'createShipment' => false,
    'payment_method' => 'paid', // or "cod"
    'amount' => 100,
    'amount_due' => 0,
    'currency' => 'SAR',
    'packageCount' => 1,
    'packageWeight' => 1,
    'orderDate' => '30/12/2026 15:45',
    'customer' => [
        'name' => 'John Doe',
        'email' => 'john@doe.com',
        'mobile' => '966500000000',
        'address' => 'Street 1',
        'city' => 'Riyadh',
        'country' => 'SA',
    ],
    'items' => [
        ['productId' => 1, 'name' => 'Product', 'price' => 100, 'quantity' => 1, 'sku' => 'SKU-1'],
    ],
]);

$oto->updateOrder($body);
$oto->listOrders(page: 1, filters: ['status' => 'delivered', 'minDate' => '2026-01-01', 'maxDate' => '2026-02-01']);
$oto->orderDetail('1234');
$oto->cancelOrder('1234');
$oto->holdOrder('1234', 'Waiting for stock', 'en');
$oto->unHoldOrder('1234');
$oto->updateOrderStatus(['1234', '1235'], 'delivered', 'Delivered by own fleet', '2026-10-05 12:00:00');
$oto->checkOrderAvailability('1234', ruleIds: [1, 2]);
$oto->customerNotifications('1234');
```

### Shipping prices and shipments

```php
$oto->checkOTODeliveryFee(['weight' => 1, 'originCity' => 'Riyadh', 'destinationCity' => 'Jeddah', 'height' => 10, 'width' => 10, 'length' => 10]);
$oto->checkDeliveryFee(['weight' => 1, 'totalDue' => 0, 'originCity' => 'Riyadh', 'destinationCity' => 'Jeddah']);
$oto->getDeliveryFee('1234');                    // options for an existing order
$oto->getDeliveryOptions(['city' => 'Riyadh']);  // or ['id' => 10]

$oto->createShipment('1234', deliveryOptionId: 564);
$oto->cancelShipment('1234', shipmentId: 987);
```

### Printing

```php
$oto->printAwb('1234');                                    // ['printAWBURL' => ..., 'trackingNumber' => ...]
$oto->printAwb('1234', ['printReverseShipment' => true]);
$oto->printAwb('1234', ['internationalProforma' => true]);
$oto->printLabel('1234', type: 'awb', returnUrl: true);    // proforma, awb, orderDetailsTemplate, return-order
```

### Tracking

```php
$oto->orderStatus('1234');
$oto->orderStatus('1234', ['labelType' => 'ZPL']);
$oto->orderHistory(['1234', '1235']);
$oto->trackShipment(['trackingNumber' => 'ASD00123', 'deliveryCompanyName' => 'aramex']);
```

### Returns

```php
$oto->createReturnShipment(['orderId' => '1234', 'deliveryOptionId' => 564, 'pickupLocationCode' => 'jdd_wh', 'items' => [/* ... */]]);
$oto->getReturnLink('1234');
$oto->getReturnDetails('1234');
$oto->triggerReturnSms('1234');
```

### Pickup locations

```php
$oto->createPickupLocation(['type' => 'warehouse', 'code' => 'jdd_wh', 'name' => 'Jeddah WH', 'city' => 'Jeddah', 'country' => 'SA' /* ... */]);
$oto->updatePickupLocation(['code' => 'jdd_wh', 'status' => 'active' /* ... */]);
$oto->getPickupLocationList(['status' => 'active']);
$oto->pickupLocationWorkingHours([/* ... */]);
```

### Account and transactions

```php
$oto->healthCheck();
$oto->accountInfo();
$oto->buyCredit(500);
$oto->requestMobileVerification('966500000000');
$oto->verifyMobileNumber('966500000000', $code, $token);
$oto->shippingPriceTransactions(['orderId' => '1234']);
$oto->creditTransactions(['page' => 1, 'perPage' => 50, 'minDate' => '2026-01-01', 'maxDate' => '2026-02-01']);
$oto->shipmentTransactions(['page' => 1]);
$oto->codTransactions(['page' => 1]);
```

### Products, boxes and stock

```php
$oto->createProduct(['productName' => 'T-shirt', 'sku' => 'TS-1', 'price' => 50]);
$oto->productList(currentPage: 1, pageSize: 50);
$oto->addBox('medium', 30, 20, 15);          // name, length, width, height (cm)
$oto->updateBox('medium', 30, 25, 15);
$oto->getBox('medium');                      // or getBox() for all boxes

$oto->updateStockQuantity(['actionType' => 'adjust', 'locationCode' => 'jdd_wh', 'sku' => 'TS-1', 'qty' => 10]);
$oto->checkInventoryStock(['TS-1', 'TS-2']);
$oto->checkGlobalStock('TS-1');
$oto->createInventoryOrder([/* ... */]);
$oto->updatePackingStatus('1234', 'packed');
$oto->getPackingOrders('jdd_wh');
$oto->availableStoresForPickup($items, selectedCity: 'Riyadh');
$oto->availableCitiesForPickup();
```

### Coverage, cities and addresses

```php
$oto->checkCoverage(['lat' => 24.7, 'lon' => 46.6, 'city' => 'Riyadh']);
$oto->availableCities(limit: 50);
$oto->availableTimeslots(['serviceType' => '...', 'packageSize' => 'small', 'lat' => 24.7, 'lon' => 46.6]);
$oto->getCities('SA', page: 1, perPage: 100);
$oto->getDeliveryEstimation([/* ... */]);
$oto->aiEstimatedDeliveryDates([/* ... */]);
$oto->nationalAddressFromShortCode('RGUC8214');
```

### Any other endpoint

Endpoints without a dedicated method (marketplace, brands, sales channels, OTO Flex, carrier
activation, ...) use the same authentication and retry handling through `call()` and `request()`:

```php
$oto->call('GET', '/rest/v2/getBrandList');                                       // array|null
$oto->call('POST', '/rest/v2/assignDriver', ['orderIDs' => ['1234'], 'driverID' => 5]);
$oto->call('GET', '/rest/v2/creditTransactions', query: ['page' => 2]);

$response = $oto->request('GET', '/rest/v2/salesChannel/getSalesChannelsList');   // Illuminate\Http\Client\Response
$response->status();
```

## Webhooks

### Registering

```php
$oto->setWebhook();                                    // defaults from the "webhook" config section
$oto->setWebhook(['webhookType' => 'shipmentError']);  // override any field
$oto->listWebhooks();                                  // or listWebhooks($id)
$oto->updateWebhook(59, ['url' => 'https://example.com/oto']);
$oto->deleteWebhook(59);
```

OTO allows up to 10 webhooks per account and supports four types: `orderStatus`, `shipmentError`,
`newOrders` and `walletTransaction`.

> [!WARNING]
> The package exposes `GET /tryoto/set-webhook` (route `tryoto.set-webhook`) for quick setup. It has
> no middleware, so publish the routes file (`--tag=routes`) and protect or remove it in production.

### Receiving

OTO calls `POST|PUT /tryoto/webhook/callback` (route `tryoto.callback`). The controller:

1. returns `401` if `authorization_key` is set and the `Authorization` header doesn't match it (with or without a `Bearer ` prefix);
2. returns `401` if `verify_signature` is enabled and the signature is invalid. OTO signs `orderId:status:timestamp` with HmacSHA256 using your `secret_key`, Base64 encoded;
3. returns `422` for an empty payload;
4. dispatches `Siberfx\LaravelTryoto\app\Events\TryotoWebhookReceived` and responds `{"success": true}`.

Handle the event with a listener:

```php
use Illuminate\Support\Facades\Event;
use Siberfx\LaravelTryoto\app\Events\TryotoWebhookReceived;

Event::listen(function (TryotoWebhookReceived $event) {
    $orderId  = $event->payload['orderId'];
    $status   = $event->payload['status'] ?? null;          // shipmentProcessing, delivered, returned, ...
    $tracking = $event->payload['trackingNumber'] ?? null;

    Order::where('reference', $orderId)->update(['shipping_status' => $status]);
});
```

To verify a signature yourself, for example in your own route:

```php
TryotoService::verifyWebhookSignature($request->all(), config('laravel-tryoto.tryoto.webhook.secret_key'));
```

## Error handling

Non-2xx responses aren't thrown as exceptions. OTO's error body is returned so you can inspect it:

```php
$result = $oto->cancelOrder('missing');
// ['success' => false, 'otoErrorCode' => 'OTO1002', 'otoErrorMessage' => 'The order ID does not exist']

if (($result['success'] ?? false) !== true) {
    report(new RuntimeException($result['otoErrorMessage'] ?? 'OTO request failed'));
}
```

Use `request()` when you need the HTTP status code. Only a failed token exchange throws:

```php
use Siberfx\LaravelTryoto\app\Exceptions\TryotoException;

try {
    $oto->accountInfo();
} catch (TryotoException $e) {
    $e->getMessage();   // "OTO authorization failed: Refresh Token is required (HTTP 400)"
    $e->response;       // the Illuminate\Http\Client\Response from /refreshToken
}
```

The full list of `OTO1xxx` error codes is in the "Error Codes" section of the [API docs](https://apis.tryoto.com/).

## Endpoint reference

| Section            | Endpoint                                              | Method                                   |
| ------------------ | ----------------------------------------------------- | ---------------------------------------- |
| Authorization      | `POST /rest/v2/refreshToken`                          | `authorize()`                            |
|                    | `GET /rest/v2/healthCheck`                            | `healthCheck()`                          |
| Account            | `GET /rest/v2/accountInfo`                            | `accountInfo()`                          |
|                    | `POST /rest/v2/buyCredit`                             | `buyCredit()`                            |
|                    | `POST /rest/v2/requestMobileVerification`             | `requestMobileVerification()`            |
|                    | `POST /rest/v2/verifyMobileNumber`                    | `verifyMobileNumber()`                   |
| Transactions       | `POST /rest/v2/getShippingPriceTransactionsList`      | `shippingPriceTransactions()`            |
|                    | `GET /rest/v2/creditTransactions`                     | `creditTransactions()`                   |
|                    | `GET /rest/v2/shipmentTransactions`                   | `shipmentTransactions()`                 |
|                    | `GET /rest/v2/codTransactions`                        | `codTransactions()`                      |
| Orders             | `POST /rest/v2/createOrder`                           | `createOrder()`                          |
|                    | `POST /rest/v2/updateOrder`                           | `updateOrder()`                          |
|                    | `POST /rest/v2/updateOrderStatus`                     | `updateOrderStatus()`                    |
|                    | `POST /rest/v2/cancelOrder`                           | `cancelOrder()`                          |
|                    | `GET /rest/v2/orders`                                 | `listOrders()`                           |
|                    | `POST /rest/v2/holdOrder`                             | `holdOrder()`                            |
|                    | `POST /rest/v2/unHoldOrder`                           | `unHoldOrder()`                          |
|                    | `GET /rest/v2/orderDetails`                           | `orderDetail()`                          |
|                    | `POST /rest/v2/checkOrderAvailability`                | `checkOrderAvailability()`               |
|                    | `GET /rest/v2/orders/{orderId}/customer-notifications`| `customerNotifications()`                |
| Shipping prices    | `POST /rest/v2/checkOTODeliveryFee`                   | `checkOTODeliveryFee()`                  |
|                    | `POST /rest/v2/checkDeliveryFee`                      | `checkDeliveryFee()`                     |
|                    | `POST /rest/v2/getDeliveryFee`                        | `getDeliveryFee()`                       |
|                    | `GET /rest/v2/getDeliveryOptions`                     | `getDeliveryOptions()`                   |
| Shipments          | `POST /rest/v2/createShipment`                        | `createShipment()`                       |
|                    | `POST /rest/v2/cancelShipment`                        | `cancelShipment()`                       |
| Print              | `GET /rest/v2/print/{orderId}`                        | `printAwb()`                             |
|                    | `POST /rest/v2/printLabel`                            | `printLabel()`                           |
| Tracking           | `POST /rest/v2/orderStatus`                           | `orderStatus()`                          |
|                    | `POST /rest/v2/orderHistory`                          | `orderHistory()`                         |
|                    | `POST /rest/v2/trackShipment`                         | `trackShipment()`                        |
| Return shipments   | `POST /rest/v2/createReturnShipment`                  | `createReturnShipment()`                 |
|                    | `POST /rest/v2/getReturnLink`                         | `getReturnLink()`                        |
|                    | `POST /rest/v2/getReturnDetails`                      | `getReturnDetails()`                     |
|                    | `POST /rest/v2/triggerReturnSms`                      | `triggerReturnSms()`                     |
| Pickup locations   | `POST /rest/v2/createPickupLocation`                  | `createPickupLocation()`                 |
|                    | `POST /rest/v2/updatePickupLocation`                  | `updatePickupLocation()`                 |
|                    | `GET /rest/v2/getPickupLocationList`                  | `getPickupLocationList()`                |
|                    | `POST /rest/v2/pickupLocationWorkingHours`            | `pickupLocationWorkingHours()`           |
| Webhooks           | `POST /rest/v2/webhook`                               | `setWebhook()`                           |
|                    | `GET /rest/v2/webhook`                                | `listWebhooks()`                         |
|                    | `PUT /rest/v2/webhook`                                | `updateWebhook()`                        |
|                    | `DELETE /rest/v2/webhook`                             | `deleteWebhook()`                        |
| Products           | `POST /rest/v2/createProduct`                         | `createProduct()`                        |
|                    | `POST /rest/v2/productList`                           | `productList()`                          |
|                    | `POST /rest/v2/addBox`                                | `addBox()`                               |
|                    | `POST /rest/v2/updateBox`                             | `updateBox()`                            |
|                    | `GET /rest/v2/getBox`                                 | `getBox()`                               |
| Stock management   | `POST /rest/v2/updateStockQuantity`                   | `updateStockQuantity()`                  |
|                    | `GET /rest/v2/checkInventoryStock`                    | `checkInventoryStock()`                  |
|                    | `GET /rest/v2/checkGlobalStock`                       | `checkGlobalStock()`                     |
|                    | `POST /rest/v2/createInventoryOrder`                  | `createInventoryOrder()`                 |
|                    | `POST /rest/v2/updatePackingStatus`                   | `updatePackingStatus()`                  |
|                    | `POST /rest/v2/getPackingOrders`                      | `getPackingOrders()`                     |
|                    | `POST /rest/v2/availableStoresForPickup`              | `availableStoresForPickup()`             |
|                    | `POST /rest/v2/availableCitiesForPickup`              | `availableCitiesForPickup()`             |
| Carrier & coverage | `POST /rest/v2/checkCoverage`                         | `checkCoverage()`                        |
|                    | `POST /rest/v2/availableCities`                       | `availableCities()`                      |
|                    | `POST /rest/v2/availableTimeslots`                    | `availableTimeslots()`                   |
|                    | `POST /rest/v2/getCities`                             | `getCities()`                            |
|                    | `POST /rest/v2/getDeliveryEstimation`                 | `getDeliveryEstimation()`                |
|                    | `POST /rest/v2/aiEstimatedDeliveryDates`              | `aiEstimatedDeliveryDates()`             |
| National address   | `POST /rest/v2/getNationalAddressFromShortCode`       | `nationalAddressFromShortCode()`         |
| Anything else      | any                                                   | `call()` / `request()`                   |

## Testing your application

The package uses Laravel's HTTP client, so `Http::fake()` keeps your tests away from the real API:

```php
use Illuminate\Support\Facades\Http;
use Siberfx\LaravelTryoto\app\Services\TryotoService;

it('ships an order', function () {
    Http::fake([
        '*/rest/v2/refreshToken' => Http::response(['access_token' => 'fake-token']),
        '*/rest/v2/createShipment' => Http::response(['success' => true, 'shipmentId' => 1]),
    ]);

    app(TryotoService::class)->createShipment('1234', 564);

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/rest/v2/createShipment')
        && $request['orderId'] === '1234');
});
```

## Testing the package

```bash
composer install
composer test           # vendor/bin/pest
composer test-coverage  # requires Xdebug or PCOV
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for what changed in each release.

## Contributing

Pull requests are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

If you find a security issue, email **info@siberfx.com** instead of opening an issue. See
[SECURITY.md](SECURITY.md).

## Credits

- [Selim Görmüş](https://siberfx.com) - Lead Software Engineer & Maintainer
- [All contributors](https://github.com/siberfx/laravel-tryoto/contributors)

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.
