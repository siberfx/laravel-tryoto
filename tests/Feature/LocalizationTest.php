<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Siberfx\LaravelTryoto\app\Events\TryotoWebhookReceived;
use Siberfx\LaravelTryoto\app\Mail\TryotoNotificationMail;
use Siberfx\LaravelTryoto\app\Notifications\TryotoMessage;
use Siberfx\LaravelTryoto\TryotoServiceProvider;

function deliveredPayload(): array
{
    return [
        'orderId' => '1234',
        'status' => 'delivered',
        'deliveryCompany' => 'aramex',
        'trackingNumber' => 'ASD00123',
        'brandedTrackingURL' => 'https://app.tryoto.com/track?key=abc',
    ];
}

it('defaults to english', function () {
    expect(config('laravel-tryoto.tryoto.notifications.locale'))->toBe('en')
        ->and(TryotoMessage::defaultLocale())->toBe('en');

    $message = TryotoMessage::fromWebhook(deliveredPayload());

    expect($message->locale)->toBe('en')
        ->and($message->title)->toBe('✅ Order 1234 is delivered')
        ->and(array_keys($message->fields))->toBe(['Status', 'Carrier', 'Tracking number'])
        ->and($message->urlLabel)->toBe('Track shipment');
});

it('ignores the application locale', function () {
    app()->setLocale('tr');

    expect(TryotoMessage::fromWebhook(deliveredPayload())->title)->toBe('✅ Order 1234 is delivered');
});

it('translates order status messages to turkish', function () {
    config(['laravel-tryoto.tryoto.notifications.locale' => 'tr']);

    $message = TryotoMessage::fromWebhook(deliveredPayload());

    expect($message->locale)->toBe('tr')
        ->and($message->title)->toBe('✅ 1234 numaralı sipariş: teslim edildi')
        ->and($message->fields)->toBe([
            'Durum' => 'delivered',
            'Kargo firması' => 'aramex',
            'Takip numarası' => 'ASD00123',
        ])
        ->and($message->urlLabel)->toBe('Gönderiyi takip et');
});

it('translates every webhook type to turkish', function (array $payload, string $title, string $field) {
    $message = TryotoMessage::fromWebhook($payload, 'tr');

    expect($message->title)->toBe($title)
        ->and($message->fields)->toHaveKey($field);
})->with([
    'shipment error' => [['orderId' => '7523', 'errorMessage' => 'City is empty', 'errorCode' => 'deliveryCompanyError'], '⚠️ 7523 numaralı siparişte gönderi hatası', 'Hata'],
    'new order' => [['order' => ['incrementId' => 'OID-1', 'grandTotal' => 10, 'currency' => 'SAR']], '🆕 Yeni sipariş OID-1', 'Toplam'],
    'wallet transaction' => [['orderId' => 'OID-1', 'amount' => -29, 'transactionStatus' => 'completed', 'remainingAmount' => 2820], '💳 Cüzdan işlemi -29', 'Kalan bakiye'],
    'out for delivery' => [['orderId' => '9', 'status' => 'outForDelivery', 'driverName' => 'Ali'], '🚚 9 numaralı sipariş: dağıtımda', 'Kurye'],
]);

it('accepts the locale as an argument', function () {
    expect(TryotoMessage::fromWebhook(deliveredPayload(), 'tr')->title)->toBe('✅ 1234 numaralı sipariş: teslim edildi')
        ->and(TryotoMessage::fromWebhook(deliveredPayload(), 'en')->title)->toBe('✅ Order 1234 is delivered');
});

it('shows unknown status codes as sent', function () {
    expect(TryotoMessage::fromWebhook(['orderId' => '1', 'status' => 'brandNewStatus'], 'tr')->title)
        ->toBe('📦 1 numaralı sipariş: brandNewStatus')
        ->and(TryotoMessage::statusLabel('brandNewStatus'))->toBe('brandNewStatus')
        ->and(TryotoMessage::statusLabel('pickedUp', 'tr'))->toBe('teslim alındı');
});

it('falls back to english for locales without translations', function () {
    config(['laravel-tryoto.tryoto.notifications.locale' => 'de']);

    expect(TryotoMessage::fromWebhook(deliveredPayload())->title)->toBe('✅ Order 1234 is delivered');
});

it('uses translations published to the application', function () {
    app('translator')->addLines(['notifications.titles.order_status' => ':icon Bestellung :order ist :status'], 'de', 'tryoto');
    app('translator')->addLines(['notifications.statuses.delivered' => 'zugestellt'], 'de', 'tryoto');

    expect(TryotoMessage::fromWebhook(deliveredPayload(), 'de')->title)->toBe('✅ Bestellung 1234 ist zugestellt')
        ->and(TryotoMessage::fromWebhook(deliveredPayload(), 'de')->fields)->toHaveKey('Status'); // untranslated lines fall back to english
});

it('translates booleans and the default link label', function () {
    $message = TryotoMessage::make('x', 'tr')->field('Kapıda ödeme', true)->link('https://x.test');

    expect($message->fields)->toBe(['Kapıda ödeme' => 'evet'])
        ->and($message->urlLabel)->toBe('Aç')
        ->and(TryotoMessage::make('x')->urlLabel)->toBe('Open');
});

it('keeps both language files in sync', function () {
    $flatten = function (array $lines, string $prefix = '') use (&$flatten): array {
        $keys = [];
        foreach ($lines as $key => $value) {
            $keys = array_merge($keys, is_array($value) ? $flatten($value, "{$prefix}{$key}.") : ["{$prefix}{$key}"]);
        }

        return $keys;
    };

    $en = $flatten(require __DIR__ . '/../../src/lang/en/notifications.php');
    $tr = $flatten(require __DIR__ . '/../../src/lang/tr/notifications.php');

    expect($tr)->toEqualCanonicalizing($en);
});

it('sends turkish messages to every channel', function () {
    Http::fake();
    Mail::fake();
    config([
        'laravel-tryoto.tryoto.notifications.locale' => 'tr',
        'laravel-tryoto.tryoto.notifications.slack.webhook_url' => 'https://hooks.slack.com/services/x',
        'laravel-tryoto.tryoto.notifications.telegram.bot_token' => '123:abc',
        'laravel-tryoto.tryoto.notifications.telegram.chat_id' => '1',
        'laravel-tryoto.tryoto.notifications.mail.to' => 'ops@shop.test',
    ]);

    TryotoWebhookReceived::dispatch(deliveredPayload());

    Http::assertSent(fn ($request) => str_contains($request->url(), 'slack')
        && str_starts_with($request['text'], '*✅ 1234 numaralı sipariş: teslim edildi*'));
    Http::assertSent(fn ($request) => str_contains($request->url(), 'telegram')
        && str_contains($request['text'], '<b>Takip numarası:</b> ASD00123'));
    Mail::assertSent(TryotoNotificationMail::class, fn (TryotoNotificationMail $mail) => $mail->hasSubject('[OTO] ✅ 1234 numaralı sipariş: teslim edildi')
        && str_contains($mail->render(), '<html lang="tr">')
        && str_contains($mail->render(), 'Gönderiyi takip et'));
});

it('publishes the language files', function () {
    $paths = TryotoServiceProvider::pathsToPublish(TryotoServiceProvider::class, 'lang');

    expect(array_values($paths))->toBe([lang_path('vendor/tryoto')]);
});
