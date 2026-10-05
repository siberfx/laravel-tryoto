<?php

namespace Siberfx\LaravelTryoto\app\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class TryotoException extends RuntimeException
{
    public ?Response $response = null;

    public static function authorizationFailed(Response $response): self
    {
        $exception = new self(
            'OTO authorization failed: ' . ($response->json('otoErrorMessage') ?? $response->json('message') ?? 'no access_token returned') . ' (HTTP ' . $response->status() . ')',
            $response->status()
        );
        $exception->response = $response;

        return $exception;
    }
}
