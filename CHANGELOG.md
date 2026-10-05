# Changelog

All notable changes to `siberfx/laravel-tryoto` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Wrappers for every documented OTO API v2 endpoint group, split into traits under `src/app/Services/Concerns`:
  - **Account & transactions:** `healthCheck()`, `accountInfo()`, `buyCredit()`, `requestMobileVerification()`, `verifyMobileNumber()`, `shippingPriceTransactions()`, `creditTransactions()`, `shipmentTransactions()`, `codTransactions()`.
  - **Orders:** `checkOrderAvailability()`, `customerNotifications()`.
  - **Shipping prices & shipments:** `checkOTODeliveryFee()`, `checkDeliveryFee()`, `getDeliveryFee()`, `getDeliveryOptions()`, `createShipment()`, `cancelShipment()`.
  - **Printing & tracking:** `printAwb()`, `printLabel()`, `orderStatus()`, `orderHistory()`, `trackShipment()`.
  - **Returns:** `createReturnShipment()`, `getReturnLink()`, `getReturnDetails()`, `triggerReturnSms()`.
  - **Pickup locations:** `createPickupLocation()`, `updatePickupLocation()`, `getPickupLocationList()`, `pickupLocationWorkingHours()`.
  - **Webhooks:** `listWebhooks()`, `updateWebhook()`, `deleteWebhook()`, `verifyWebhookSignature()`.
  - **Products & stock:** `createProduct()`, `productList()`, `addBox()`, `updateBox()`, `getBox()`, `updateStockQuantity()`, `checkInventoryStock()`, `checkGlobalStock()`, `createInventoryOrder()`, `updatePackingStatus()`, `getPackingOrders()`, `availableStoresForPickup()`, `availableCitiesForPickup()`.
  - **Coverage:** `checkCoverage()`, `availableCities()`, `availableTimeslots()`, `getCities()`, `getDeliveryEstimation()`, `aiEstimatedDeliveryDates()`, `nationalAddressFromShortCode()`.
- Generic `request()` / `call()` methods for endpoints without a dedicated wrapper.
- Automatic token renewal: a `401` response re-authorizes and retries the request once.
- `TryotoException`, thrown when the refresh token exchange fails.
- `TryotoWebhookReceived` event, dispatched for every accepted webhook call.
- `webhook` config section (`url`, `type`, `secret_key`, `verify_signature`, `authorization_key`, `timestamp_format`, `order_prefix`) and a `timeout` option.
- `TryotoService` is registered as a container singleton.
- Pest test suite (116 tests) covering every endpoint wrapper, authentication, webhooks, the controller and the service provider.
- GitHub Actions CI for PHP 8.4/8.5 × Laravel 12/13, at both lowest and stable dependency versions.
- `LICENSE.md`, `CONTRIBUTING.md`, `SECURITY.md`, `.gitattributes` and `.editorconfig`.

### Changed

- **Breaking:** `setWebhook()` returns the decoded response array instead of the raw `Illuminate\Http\Client\Response`.
- **Breaking:** the webhook callback returns a JSON response and dispatches `TryotoWebhookReceived`. Move code that lived in an overridden `listenWebhook()` into an event listener.
- The access token is fetched on the first API call instead of in the constructor, so resolving the service no longer makes an HTTP request.
- The sandbox token is cached under its own key (`<cache_name>_sandbox`), so switching environments never reuses the other environment's token.
- `setWebhook()` accepts overrides and reads its defaults from config.
- The webhook callback route accepts `PUT` as well as `POST`, and checks the configured authorization key and (optionally) the signature.
- The controller resolves `TryotoService` from the container.

### Fixed

- `setWebhook()` was sent without the `Authorization` header, so OTO rejected it. In sandbox mode it also registered a hardcoded placeholder URL.
- `productsParser()` hit undefined index errors for products without `taxAmount`, `serialnumber` or `image` (the bundled sample data triggered it).
- `(double)` casts replaced with `(float)`; the former are deprecated on PHP 8.5.

## [2.0.0] - 2026-06-17

### Added

- `holdOrder()`, `unHoldOrder()` and `updateOrderStatus()` service methods (with matching controller actions) for the documented `/rest/v2/holdOrder`, `/rest/v2/unHoldOrder` and `/rest/v2/updateOrderStatus` endpoints.
- `listOrders()` accepts an optional `$filters` array (`status`, `minDate`, `maxDate`) in addition to pagination.

### Changed

- Require PHP `^8.4`.
- Support Laravel `^12.0|^13.0` (dropped Laravel 11).
- Added property/return types and switched `cacheTime` to `int`.
- The service provider merges config in `register()` and loads routes/publishes assets in `boot()`.
- `listOrders()` uses the official `GET /rest/v2/orders` instead of the undocumented `/rest/v2/getAllOrders`.

### Fixed

- **Config paths:** `TryotoService` read `services.tryoto.sandbox`, `laravel-tryoto.cache_name` and `laravel-tryoto.cache_time`, none of which exist. Sandbox mode never activated and token caching silently failed. It now reads `laravel-tryoto.tryoto.*`.
- **`listOrders()` authorization:** sent the refresh token instead of the Bearer access token, causing 401s.
- **`listOrders()` pagination:** `page`/`perPage` were passed as URI-template placeholders and dropped. They are now sent as query parameters.
- **`cancelOrder()`:** used `GET` with a query parameter; the OTO docs specify `POST /rest/v2/cancelOrder` with `orderId` in the body.
- **Routes never registered:** `setupRoutes()` was never invoked, so the `tryoto.callback` route did not exist and the service constructor threw `RouteNotFoundException` in live mode.

### Removed

- The broken deferred-provider setup (`$defer`/`provides()`) that referenced an unbound `tryoto` service.

## [1.0.1] - 2024-08-04

### Changed

- Updated the default configuration file.

## [1.0.0] - 2024-08-04

### Added

- Initial release: `TryotoService` with token caching, `listOrders()`, `orderDetail()`, `createOrder()`, `updateOrder()`, `cancelOrder()` and `setWebhook()`, a sample controller and the webhook callback route.

[Unreleased]: https://github.com/siberfx/laravel-tryoto/compare/2.0.0...HEAD
[2.0.0]: https://github.com/siberfx/laravel-tryoto/compare/1.0.1...2.0.0
[1.0.1]: https://github.com/siberfx/laravel-tryoto/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/siberfx/laravel-tryoto/releases/tag/1.0.0
