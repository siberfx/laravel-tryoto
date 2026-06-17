# Changelog

All notable changes to `laravel-tryoto` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Added
- `holdOrder()`, `unHoldOrder()`, and `updateOrderStatus()` service methods (with matching controller actions) for the documented `/rest/v2/holdOrder`, `/rest/v2/unHoldOrder`, and `/rest/v2/updateOrderStatus` endpoints.
- `listOrders()` now accepts an optional `$filters` array (`status`, `minDate`, `maxDate`) in addition to pagination.

### Changed
- Require PHP `^8.4`.
- Support Laravel `^12.0|^13.0` (dropped Laravel 11).
- Added property/return types and switched `cacheTime` to `int`.
- Service provider now merges config in `register()` and loads routes/publishes assets in `boot()`.
- **`listOrders()` endpoint**: switched from the undocumented `/rest/v2/getAllOrders` to the official `GET /rest/v2/orders`.

### Fixed
- **Config paths**: `TryotoService` read `services.tryoto.sandbox`, `laravel-tryoto.cache_name`, and `laravel-tryoto.cache_time`, none of which exist. Sandbox mode never activated and caching/token storage silently failed. Now reads `laravel-tryoto.tryoto.*`.
- **`listOrders()` authorization**: sent the refresh token instead of the Bearer access token (causing 401s). Now uses the access token like the other endpoints.
- **`listOrders()` pagination**: `page`/`perPage` were passed via `withUrlParameters()` (URI-template placeholders) against a URL with no placeholders, so they were dropped. Now sent as query parameters.
- **`cancelOrder()` method**: the OTO docs specify `POST /rest/v2/cancelOrder` with `orderId` in the body; the package used a `GET` request with a query parameter. Now sends a `POST` with a JSON body.
- **Routes never registered**: the provider defined `setupRoutes()` but never invoked it, so the `tryoto.callback` route did not exist and `TryotoService::__construct()` threw `RouteNotFoundException` in live mode. Routes are now loaded during `boot()`.

### Removed
- Removed the broken deferred-provider setup (`$defer`/`provides()`) that referenced an unbound `tryoto` service.
