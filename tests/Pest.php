<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Siberfx\LaravelTryoto\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * The API requests recorded by Http::fake(), excluding the token exchange.
 *
 * @return list<Request>
 */
function apiRequests(): array
{
    return Http::recorded()
        ->map(fn (array $pair) => $pair[0])
        ->reject(fn (Request $request) => str_ends_with($request->url(), '/rest/v2/refreshToken'))
        ->values()
        ->all();
}

/**
 * The single API request sent by the code under test.
 */
function lastApiRequest(): Request
{
    $requests = apiRequests();

    expect($requests)->not->toBeEmpty();

    return end($requests);
}

/**
 * Decoded JSON body of a recorded request ([] when it has none).
 */
function jsonBody(Request $request): array
{
    return $request->body() === '' ? [] : json_decode($request->body(), true);
}
