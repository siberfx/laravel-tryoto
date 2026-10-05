<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Siberfx\LaravelTryoto\app\Events\TryotoWebhookReceived;
use Siberfx\LaravelTryoto\app\Jobs\SendTryotoNotification;
use Siberfx\LaravelTryoto\app\Mail\TryotoNotificationMail;
use Siberfx\LaravelTryoto\app\Notifications\Channels\NotificationChannel;
use Siberfx\LaravelTryoto\app\Notifications\Channels\SlackChannel;
use Siberfx\LaravelTryoto\app\Notifications\Channels\TelegramChannel;
use Siberfx\LaravelTryoto\app\Notifications\TryotoMessage;
use Siberfx\LaravelTryoto\app\Notifications\TryotoNotifier;

const SLACK_URL = 'https://hooks.slack.com/services/T000/B000/XXXX';
const TELEGRAM_URL = 'https://api.telegram.org/bot123:abc/sendMessage';

function enableSlack(): void
{
    config(['laravel-tryoto.tryoto.notifications.slack.webhook_url' => SLACK_URL]);
}

function enableTelegram(): void
{
    config([
        'laravel-tryoto.tryoto.notifications.telegram.bot_token' => '123:abc',
        'laravel-tryoto.tryoto.notifications.telegram.chat_id' => '-100200',
    ]);
}

function orderStatusPayload(array $overrides = []): array
{
    return array_merge([
        'orderId' => '1234',
        'status' => 'delivered',
        'deliveryCompany' => 'aramex',
        'trackingNumber' => 'ASD00123',
        'brandedTrackingURL' => 'https://app.tryoto.com/track?key=abc',
        'timestamp' => '1595941360328',
    ], $overrides);
}

function chatRequests(): array
{
    return Http::recorded()
        ->map(fn (array $pair) => $pair[0])
        ->filter(fn (Request $request) => str_contains($request->url(), 'slack.com') || str_contains($request->url(), 'telegram.org'))
        ->values()
        ->all();
}

beforeEach(fn () => Http::fake([
    'hooks.slack.com/*' => Http::response('ok'),
    'api.telegram.org/*' => Http::response(['ok' => true]),
]));

describe('configuration', function () {
    it('ships with every channel disabled', function () {
        expect(config('laravel-tryoto.tryoto.notifications'))->toMatchArray([
            'types' => null,
            'statuses' => null,
            'queue' => null,
            'queue_connection' => null,
        ])
            ->and(config('laravel-tryoto.tryoto.notifications.slack.webhook_url'))->toBeNull()
            ->and(config('laravel-tryoto.tryoto.notifications.telegram.bot_token'))->toBeNull()
            ->and(config('laravel-tryoto.tryoto.notifications.telegram.chat_id'))->toBeNull()
            ->and(app(TryotoNotifier::class)->enabled())->toBeFalse();
    });

    it('sends nothing when no channel is configured', function () {
        $this->postJson('/tryoto/webhook/callback', orderStatusPayload())->assertOk();

        Http::assertNothingSent();
    });

    it('needs both bot token and chat id for telegram', function () {
        config(['laravel-tryoto.tryoto.notifications.telegram.bot_token' => '123:abc']);

        expect(app(TryotoNotifier::class)->channels())->toBe([]);
    });

    it('lists only configured channels', function () {
        enableTelegram();

        expect(array_keys(app(TryotoNotifier::class)->channels()))->toBe(['telegram']);
    });
});

describe('delivery', function () {
    it('posts webhook events to slack', function () {
        enableSlack();

        $this->postJson('/tryoto/webhook/callback', orderStatusPayload())->assertOk();

        $request = chatRequests()[0];
        $text = "*✅ Order 1234 is delivered*\n*Status:* delivered\n*Carrier:* aramex\n*Tracking number:* ASD00123\n<https://app.tryoto.com/track?key=abc|Track shipment>";

        expect(chatRequests())->toHaveCount(1)
            ->and($request->url())->toBe(SLACK_URL)
            ->and($request['text'])->toBe($text)
            ->and($request['blocks'])->toBe([['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => $text]]])
            ->and($request->data())->not->toHaveKeys(['channel', 'username']);
    });

    it('passes the optional slack channel and username', function () {
        enableSlack();
        config([
            'laravel-tryoto.tryoto.notifications.slack.channel' => '#shipping',
            'laravel-tryoto.tryoto.notifications.slack.username' => 'OTO',
        ]);

        app(TryotoNotifier::class)->send(TryotoMessage::make('Hi'));

        expect(chatRequests()[0]['channel'])->toBe('#shipping')
            ->and(chatRequests()[0]['username'])->toBe('OTO');
    });

    it('posts webhook events to telegram', function () {
        enableTelegram();

        $this->postJson('/tryoto/webhook/callback', orderStatusPayload())->assertOk();

        $request = chatRequests()[0];

        expect($request->url())->toBe(TELEGRAM_URL)
            ->and($request['chat_id'])->toBe('-100200')
            ->and($request['parse_mode'])->toBe('HTML')
            ->and($request['link_preview_options'])->toBe(['is_disabled' => true])
            ->and($request->data())->not->toHaveKey('message_thread_id')
            ->and($request['text'])->toBe("<b>✅ Order 1234 is delivered</b>\n<b>Status:</b> delivered\n<b>Carrier:</b> aramex\n<b>Tracking number:</b> ASD00123\n<a href=\"https://app.tryoto.com/track?key=abc\">Track shipment</a>");
    });

    it('uses the telegram thread id and custom api url', function () {
        enableTelegram();
        config([
            'laravel-tryoto.tryoto.notifications.telegram.thread_id' => '42',
            'laravel-tryoto.tryoto.notifications.telegram.api_url' => 'https://tg.example.com/',
        ]);
        Http::fake(['tg.example.com/*' => Http::response(['ok' => true])]);

        app(TryotoNotifier::class)->send(TryotoMessage::make('Hi'));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://tg.example.com/bot123:abc/sendMessage'
            && $request['message_thread_id'] === '42');
    });

    it('sends to every configured channel', function () {
        enableSlack();
        enableTelegram();

        expect(app(TryotoNotifier::class)->send(TryotoMessage::make('Hi')))->toBe(['slack', 'telegram'])
            ->and(chatRequests())->toHaveCount(2);
    });

    it('keeps going and reports when a channel fails', function () {
        Exceptions::fake();
        enableTelegram();
        // the beforeEach fake answers hooks.slack.com, so point Slack at a host that fails
        config(['laravel-tryoto.tryoto.notifications.slack.webhook_url' => 'https://failing.slack.com/services/x']);
        Http::fake(['failing.slack.com/*' => Http::response('invalid_token', 403)]);

        $this->postJson('/tryoto/webhook/callback', orderStatusPayload())->assertOk();

        expect(chatRequests())->toHaveCount(2);
        Exceptions::assertReported(\Illuminate\Http\Client\RequestException::class);
    });

    it('supports custom channels', function () {
        $channel = new class implements NotificationChannel {
            public array $messages = [];

            public function isConfigured(): bool
            {
                return true;
            }

            public function send(TryotoMessage $message): void
            {
                $this->messages[] = $message->title;
            }
        };

        app(TryotoNotifier::class)->extend('discord', $channel);

        TryotoWebhookReceived::dispatch(orderStatusPayload(['status' => 'returned']));

        expect($channel->messages)->toBe(['↩️ Order 1234 is returned']);
    });
});

describe('mail', function () {
    beforeEach(fn () => Mail::fake());

    it('is disabled until recipients are set', function () {
        expect(config('laravel-tryoto.tryoto.notifications.mail.to'))->toBeNull()
            ->and(config('laravel-tryoto.tryoto.notifications.mail.mailer'))->toBeNull()
            ->and(app(TryotoNotifier::class)->channels())->toBe([]);

        TryotoWebhookReceived::dispatch(orderStatusPayload());

        Mail::assertNothingSent();
    });

    it('emails webhook events to the configured recipients', function () {
        config(['laravel-tryoto.tryoto.notifications.mail.to' => 'ops@shop.test, owner@shop.test ,']);

        $this->postJson('/tryoto/webhook/callback', orderStatusPayload())->assertOk();

        Mail::assertSent(TryotoNotificationMail::class, function (TryotoNotificationMail $mail) {
            return $mail->hasTo('ops@shop.test')
                && $mail->hasTo('owner@shop.test')
                && count($mail->to) === 2
                && $mail->hasSubject('[OTO] ✅ Order 1234 is delivered')
                && $mail->mailer === null;
        });
        expect(chatRequests())->toBe([]);
    });

    it('accepts recipients as an array', function () {
        config(['laravel-tryoto.tryoto.notifications.mail.to' => ['a@shop.test', 'b@shop.test']]);

        app(TryotoNotifier::class)->send(TryotoMessage::make('Hi'));

        Mail::assertSent(TryotoNotificationMail::class, fn ($mail) => $mail->hasTo('a@shop.test') && $mail->hasTo('b@shop.test'));
    });

    it('uses the configured mailer, sender and subject prefix', function () {
        config(['laravel-tryoto.tryoto.notifications.mail' => [
            'to' => 'ops@shop.test',
            'mailer' => 'ses',
            'from_address' => 'shipping@shop.test',
            'from_name' => 'Shop Shipping',
            'subject_prefix' => '',
        ]]);

        app(TryotoNotifier::class)->send(TryotoMessage::make('Hi'));

        Mail::assertSent(TryotoNotificationMail::class, fn (TryotoNotificationMail $mail) => $mail->mailer === 'ses'
            && $mail->hasFrom('shipping@shop.test', 'Shop Shipping')
            && $mail->hasSubject('Hi'));
    });

    it('sends alongside the chat channels', function () {
        enableSlack();
        config(['laravel-tryoto.tryoto.notifications.mail.to' => 'ops@shop.test']);

        expect(app(TryotoNotifier::class)->send(TryotoMessage::make('Hi')))->toBe(['slack', 'mail']);
        Mail::assertSentCount(1);
    });

    it('renders the fields and link as escaped html', function () {
        $mail = new TryotoNotificationMail(
            TryotoMessage::make('Order <1> delivered')
                ->field('Carrier', 'aramex & co')
                ->link('https://track.test/?a=1&b=2', 'Track shipment'),
        );

        $html = $mail->render();

        expect($html)->toContain('Order &lt;1&gt; delivered')
            ->toContain('>Carrier</td>')
            ->toContain('aramex &amp; co')
            ->toContain('href="https://track.test/?a=1&amp;b=2"')
            ->toContain('Track shipment')
            ->not->toContain('<1>');
    });

    it('omits the table and button when there is nothing to show', function () {
        $html = (new TryotoNotificationMail(TryotoMessage::make('Hi')))->render();

        expect($html)->not->toContain('<table')->not->toContain('<a ');
    });
});

it('delivers email through a real mailer transport', function () {
    config([
        'mail.default' => 'array',
        'mail.from' => ['address' => 'noreply@shop.test', 'name' => 'Shop'],
        'laravel-tryoto.tryoto.notifications.mail.to' => 'ops@shop.test',
    ]);

    TryotoWebhookReceived::dispatch(orderStatusPayload());

    $messages = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
    $email = $messages->first()->getOriginalMessage();

    expect($messages)->toHaveCount(1)
        ->and($email->getTo()[0]->getAddress())->toBe('ops@shop.test')
        ->and($email->getFrom()[0]->getAddress())->toBe('noreply@shop.test')
        ->and($email->getSubject())->toBe('[OTO] ✅ Order 1234 is delivered')
        ->and($email->getHtmlBody())->toContain('ASD00123');
});

describe('filters', function () {
    beforeEach(fn () => enableSlack());

    it('only notifies about the configured types', function () {
        config(['laravel-tryoto.tryoto.notifications.types' => ['shipmentError']]);

        TryotoWebhookReceived::dispatch(orderStatusPayload());
        TryotoWebhookReceived::dispatch(['orderId' => '1', 'errorCode' => 'deliveryCompanyError', 'errorMessage' => 'City is empty']);

        expect(chatRequests())->toHaveCount(1)
            ->and(chatRequests()[0]['text'])->toStartWith('*⚠️ Shipment error for order 1*');
    });

    it('only notifies about the configured order statuses', function () {
        config(['laravel-tryoto.tryoto.notifications.statuses' => ['Delivered', 'returned']]);

        foreach (['shipmentProcessing', 'delivered', 'returned', 'outForDelivery'] as $status) {
            TryotoWebhookReceived::dispatch(orderStatusPayload(['status' => $status]));
        }

        expect(chatRequests())->toHaveCount(2);
    });

    it('applies the status filter to order status webhooks only', function () {
        config(['laravel-tryoto.tryoto.notifications.statuses' => ['delivered']]);

        TryotoWebhookReceived::dispatch(['orderId' => '1', 'errorMessage' => 'boom']);

        expect(chatRequests())->toHaveCount(1);
    });
});

describe('queueing', function () {
    it('queues the notification when a queue is configured', function () {
        Queue::fake();
        enableSlack();
        config([
            'laravel-tryoto.tryoto.notifications.queue' => 'notifications',
            'laravel-tryoto.tryoto.notifications.queue_connection' => 'redis',
        ]);

        $this->postJson('/tryoto/webhook/callback', orderStatusPayload())->assertOk();

        expect(chatRequests())->toBe([]);
        Queue::assertPushedOn('notifications', SendTryotoNotification::class, function (SendTryotoNotification $job) {
            return $job->connection === 'redis' && $job->message->title === '✅ Order 1234 is delivered';
        });
    });

    it('delivers the message when the job runs', function () {
        enableSlack();

        app()->call([new SendTryotoNotification(TryotoMessage::make('Queued')), 'handle']);

        expect(chatRequests()[0]['text'])->toBe('*Queued*');
    });

    it('does not queue anything when no channel is configured', function () {
        Queue::fake();
        config(['laravel-tryoto.tryoto.notifications.queue' => 'notifications']);

        TryotoWebhookReceived::dispatch(orderStatusPayload());

        Queue::assertNothingPushed();
    });
});

describe('messages', function () {
    it('detects the webhook type from the payload', function (array $payload, string $type) {
        expect(TryotoWebhookReceived::detectType($payload))->toBe($type)
            ->and((new TryotoWebhookReceived($payload))->type())->toBe($type);
    })->with([
        'orderStatus' => [['orderId' => '1', 'status' => 'delivered'], 'orderStatus'],
        'shipmentError' => [['orderId' => '1', 'errorCode' => 'x'], 'shipmentError'],
        'newOrders' => [['order' => ['incrementId' => 'OID-1']], 'newOrders'],
        'walletTransaction' => [['orderId' => '1', 'transactionStatus' => 'completed'], 'walletTransaction'],
    ]);

    it('describes a shipment error', function () {
        $message = TryotoMessage::fromWebhook([
            'orderId' => '7523',
            'errorMessage' => 'delivery company not allow to create shipment',
            'deliveryCompanyResponse' => 'City/Zipcode is empty',
            'errorCode' => 'deliveryCompanyError',
            'deliveryCompany' => 'aramex',
        ]);

        expect($message->type)->toBe('shipmentError')
            ->and($message->title)->toBe('⚠️ Shipment error for order 7523')
            ->and($message->fields)->toBe([
                'Error' => 'delivery company not allow to create shipment',
                'Code' => 'deliveryCompanyError',
                'Carrier' => 'aramex',
                'Carrier response' => 'City/Zipcode is empty',
            ]);
    });

    it('describes a new order', function () {
        $message = TryotoMessage::fromWebhook(['order' => [
            'address' => ['name' => 'Fatma', 'city' => 'Dubai'],
            'grandTotal' => 150,
            'currency' => 'SAR',
            'paymentMethod' => 'cod',
            'incrementId' => 'OID-23331-1035',
            'salesChannel' => 'manual',
            'items' => [['sku' => '1'], ['sku' => '2']],
            'status' => 'assignedToWarehouse',
        ], 'timestamp' => 1742822768001]);

        expect($message->title)->toBe('🆕 New order OID-23331-1035')
            ->and($message->fields)->toBe([
                'Status' => 'assignedToWarehouse',
                'Total' => '150 SAR',
                'Payment' => 'cod',
                'Customer' => 'Fatma, Dubai',
                'Sales channel' => 'manual',
                'Items' => '2',
            ]);
    });

    it('describes a wallet transaction', function () {
        $message = TryotoMessage::fromWebhook([
            'amount' => -29,
            'orderId' => 'OID-1',
            'transactionStatus' => 'completed',
            'chargeType' => 'charge',
            'transactionType' => 'dcFee',
            'remainingAmount' => 2820,
            'deliveryCompanyName' => 'OTO Flex Agg.',
        ]);

        expect($message->title)->toBe('💳 Wallet transaction -29')
            ->and($message->fields)->toMatchArray(['Order' => 'OID-1', 'Remaining balance' => '2820', 'Carrier' => 'OTO Flex Agg.']);
    });

    it('includes driver and failure details for order status updates', function () {
        $message = TryotoMessage::fromWebhook(orderStatusPayload([
            'status' => 'outForDelivery',
            'driverName' => 'Ali Veli',
            'driverPhone' => '966555444333',
            'attemptFailureReason' => 'Customer was not in the house',
            'brandedTrackingURL' => null,
            'trackingUrl' => 'https://carrier.test/t/1',
        ]));

        expect($message->title)->toBe('🚚 Order 1234 is out for delivery')
            ->and($message->fields['Driver'])->toBe('Ali Veli 966555444333')
            ->and($message->fields['Failed attempt'])->toBe('Customer was not in the house')
            ->and($message->url)->toBe('https://carrier.test/t/1');
    });

    it('escapes markup for each channel', function () {
        $message = TryotoMessage::make('Order <1> & "2"')->field('Note', '<b>hi</b>');

        expect((new SlackChannel())->format($message))->toBe("*Order &lt;1&gt; &amp; \"2\"*\n*Note:* &lt;b&gt;hi&lt;/b&gt;")
            ->and((new TelegramChannel())->format($message))->toBe("<b>Order &lt;1&gt; &amp; &quot;2&quot;</b>\n<b>Note:</b> &lt;b&gt;hi&lt;/b&gt;");
    });

    it('skips empty and array fields', function () {
        $message = TryotoMessage::make('x')->field('A', null)->field('B', '')->field('C', ['x'])->field('D', 0)->field('E', true);

        expect($message->fields)->toBe(['D' => '0', 'E' => 'yes']);
    });
});
