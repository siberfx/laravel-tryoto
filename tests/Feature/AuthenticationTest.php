<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Siberfx\LaravelTryoto\app\Exceptions\TryotoException;

it('does not call the API when the service is resolved', function () {
    $this->fakeOto();

    $this->oto();

    Http::assertNothingSent();
});

it('exchanges the refresh token for a bearer access token', function () {
    $this->fakeOto();

    expect($this->oto()->authorize())->toBe('Bearer access-token');

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://api.tryoto.com/rest/v2/refreshToken'
        && $request['refresh_token'] === 'live-refresh-token');
});

it('caches the access token between calls and instances', function () {
    $this->fakeOto();

    $this->oto()->accountInfo();
    $this->oto()->healthCheck();
    app()->forgetInstance(\Siberfx\LaravelTryoto\app\Services\TryotoService::class);
    $this->oto()->accountInfo();

    Http::assertSentCount(4); // one token exchange + three API calls
    expect(Cache::get('oto_api_token'))->toBe('Bearer access-token');
});

it('reuses a token that is already cached', function () {
    Cache::put('oto_api_token', 'Bearer cached-token', now()->addHour());
    $this->fakeOto();

    $this->oto()->accountInfo();

    Http::assertSentCount(1);
    expect(lastApiRequest()->header('Authorization'))->toBe(['Bearer cached-token']);
});

it('forces a new token when asked', function () {
    Cache::put('oto_api_token', 'Bearer cached-token', now()->addHour());
    $this->fakeOto();

    expect($this->oto()->authorize(fresh: true))->toBe('Bearer access-token');
});

it('refreshes an expired token and retries once on 401', function () {
    Http::fake([
        '*/rest/v2/refreshToken' => Http::sequence()
            ->push(['access_token' => 'expired'])
            ->push(['access_token' => 'renewed']),
        '*/rest/v2/accountInfo' => Http::sequence()
            ->push(['message' => 'Unauthorized'], 401)
            ->push(['companyName' => 'ACME']),
    ]);

    expect($this->oto()->accountInfo())->toBe(['companyName' => 'ACME']);

    $calls = collect(apiRequests())->map(fn (Request $request) => $request->header('Authorization')[0]);
    expect($calls->all())->toBe(['Bearer expired', 'Bearer renewed']);
});

it('returns the second 401 instead of looping', function () {
    Http::fake([
        '*/rest/v2/refreshToken' => Http::response(['access_token' => 'token']),
        '*' => Http::response(['message' => 'Unauthorized'], 401),
    ]);

    $response = $this->oto()->request('GET', '/rest/v2/accountInfo');

    expect($response->status())->toBe(401)
        ->and(apiRequests())->toHaveCount(2);
});

it('throws when the refresh token is rejected', function () {
    Http::fake(['*' => Http::response(['otoErrorCode' => 'OTO1083', 'otoErrorMessage' => 'Refresh Token is required'], 400)]);

    $this->oto()->accountInfo();
})->throws(TryotoException::class, 'OTO authorization failed: Refresh Token is required (HTTP 400)');

it('throws when no access token is returned', function () {
    Http::fake(['*' => Http::response(['success' => true])]);

    try {
        $this->oto()->authorize();
    } catch (TryotoException $exception) {
        expect($exception->response?->status())->toBe(200)
            ->and($exception->getMessage())->toContain('no access_token returned');

        return;
    }

    $this->fail('TryotoException was not thrown');
});

it('uses the staging API and a separate cache key in sandbox mode', function () {
    config(['laravel-tryoto.tryoto.sandbox' => true]);
    Cache::put('oto_api_token', 'Bearer live-token', now()->addHour());
    $this->fakeOto();

    $this->oto()->accountInfo();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://staging-api.tryoto.com/rest/v2/refreshToken'
        && $request['refresh_token'] === 'test-refresh-token');
    expect(lastApiRequest()->url())->toBe('https://staging-api.tryoto.com/rest/v2/accountInfo')
        ->and(Cache::get('oto_api_token_sandbox'))->toBe('Bearer access-token');
});

it('trims a trailing slash from the configured base url', function () {
    config(['laravel-tryoto.tryoto.live.url' => 'https://api.tryoto.com/']);
    $this->fakeOto();

    $this->oto()->healthCheck();

    expect(lastApiRequest()->url())->toBe('https://api.tryoto.com/rest/v2/healthCheck');
});
