<?php

namespace Siberfx\LaravelTryoto\app\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Siberfx\LaravelTryoto\app\Events\TryotoWebhookReceived;
use Siberfx\LaravelTryoto\app\Services\TryotoService;

class TryOtoController
{
    public $service;

    public function __construct()
    {
        $this->service = app(TryotoService::class);
    }

    public function auth()
    {
        return $this->service->authorize();
    }

    public function cancelOrder($orderId = 'OID-60774-1001')
    {
        return $this->service->cancelOrder($orderId);
    }

    public function holdOrder($orderId, string $reason = '', string $reasonLang = 'en')
    {
        return $this->service->holdOrder($orderId, $reason, $reasonLang);
    }

    public function unHoldOrder($orderId)
    {
        return $this->service->unHoldOrder($orderId);
    }

    public function updateOrderStatus($orderIds, string $status, string $description = '', ?string $date = null)
    {
        return $this->service->updateOrderStatus($orderIds, $status, $description, $date);
    }

    public function orderDetail($orderId = 'OID-60774-1001')
    {
        return $this->service->orderDetail($orderId);
    }

    public function orderList(int $page = 1, array $filters = [])
    {
        return $this->service->listOrders($page, $filters);
    }


    public function createOrder($order)
    {
        return $this->dataSet($order);
    }

    public function setWebhook()
    {
        return $this->service->setWebhook();
    }


    public function dataSet(array $order = [])
    {
        $siteTitle = 'Shop title';

        if (empty($order) || !is_array($order)) {
            $order = [
                'orderCode' => '1231223',
                'amount' => 5.00,
                'sub_total' => 5.00,
                'description' => 'test description',
                'orderDate' => Carbon::parse(Carbon::now())->format('d/m/y H:i'),
                'deliverySlotDate' => Carbon::parse(Carbon::now()->addDay())->format('d/m/y'), // next day date
                'customer_name' => 'John Doe',
                'customer_email' => 'john@doe.com',
                'customer_phone' => '123456789',
                'customer_address' => 'test address',
                'customer_state' => 'State',
                'customer_city' => 'City',
                'customer_country' => 'TR',
                'customer_zipcode' => '12345',
                'products' => [
                    [
                        "productId" => 1,
                        "name" => 'product name 1',
                        "price" => 5.00,
                        "rowTotal" => 5,
                        "quantity" => 1,
                        "serialnumber" => "",
                        "sku" => 'SKU-Code',
                        "image" => 'https://fullpath-to-image.jpg',
                    ],
                    [
                        "productId" => 2,
                        "name" => 'product name 2',
                        "price" => 5.00,
                        "taxAmount" => 0,
                        "quantity" => 1,
                        "serialnumber" => "",
                        "sku" => 'SKU-Code 2',
                        "image" => 'https://fullpath-to-image-2.jpg',
                    ]
                ]
            ];
        }

        $body = [
            "orderId" => $order['orderCode'], // check documentation for your needs
            "ref1" => "", // check documentation for your needs
            "pickupLocationCode" => "", // check documentation for your needs
//            "deliveryOptionId" => "", // check documentation for your needs
            "serviceType" => "",
            "createShipment" => false,
            "storeName" => $siteTitle,
            "payment_method" => "paid",
            "amount" => (float)$order['amount'],
            "amount_due" => 0,
            "customsValue" => "12",
            "customsCurrency" => "TRY",
            "shippingAmount" => 20,
            "subtotal" => (float)$order['sub_total'],
            "currency" => "TRY",
            "shippingNotes" => $order['description'],
            "packageSize" => "small",
            "packageCount" => (int)count($order['products']),
            "packageWeight" => 1,
            "boxWidth" => 10, // check documentation for your needs
            "boxLength" => 10, // check documentation for your needs
            "boxHeight" => 10, // check documentation for your needs
            "orderDate" => $order['orderDate'], // "30/12/2020 15:45",
            "deliverySlotDate" => $order['deliverySlotDate'], //"31/12/2020",
            "deliverySlotTo" => "12pm",
            "deliverySlotFrom" => "6:30pm",
            "senderName" => $siteTitle,
            "customer" => [
                "name" => $order['customer_name'],
                "email" => $order['customer_email'],
                "mobile" => $order['customer_phone'],
                "address" => $order['customer_address'],
                "district" => $order['customer_state'],
                "city" => $order['customer_city'],
                "country" => $order['customer_country'],
                "postcode" => $order['customer_zipcode'],
                "lat" => "",
                "lon" => "",
                "refID" => "",
                "W3WAddress" => ""
            ],
            "items" => $this->productsParser($order['products'])
        ];

        $this->service->createOrder($body);

        return $body;
    }

    public function productsParser(array $products)
    {
        $result = [];

        foreach ($products as $single) {
            $result[] = [
                "productId" => $single['productId'],
                "name" => $single['name'],
                "price" => $single['price'],
                "rowTotal" => (float)$single['price'] * $single['quantity'],
                "taxAmount" => $single['taxAmount'] ?? 0,
                "quantity" => $single['quantity'],
                "serialnumber" => $single['serialnumber'] ?? "",
                "sku" => $single['sku'],
                "image" => $single['image'] ?? "",
            ];
        }
        return $result;
    }


    /**
     * Receives OTO webhook calls (orderStatus, shipmentError, newOrders, walletTransaction).
     *
     * When an authorization key is configured the Authorization header must match it, and when
     * signature verification is enabled the HmacSHA256 signature must match the secret key.
     * Accepted payloads are dispatched as a TryotoWebhookReceived event — listen to it in your app.
     */
    public function listenWebhook(Request $request): JsonResponse
    {
        $config = config('laravel-tryoto.tryoto.webhook', []);
        $payload = $request->all();

        $authorizationKey = (string) ($config['authorization_key'] ?? '');
        if ($authorizationKey !== '') {
            $header = (string) $request->header('Authorization', '');

            if (!hash_equals($authorizationKey, $header) && !hash_equals('Bearer ' . $authorizationKey, $header)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
        }

        if (!empty($config['verify_signature'])
            && !TryotoService::verifyWebhookSignature($payload, (string) ($config['secret_key'] ?? ''))) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        if (empty($payload)) {
            return response()->json(['success' => false, 'message' => 'Empty payload'], 422);
        }

        // orderId, status, trackingNumber, brandedTrackingURL, driverName, printAWBURL, timestamp, ...
        TryotoWebhookReceived::dispatch($payload);

        return response()->json(['success' => true]);
    }
}
