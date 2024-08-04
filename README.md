### v1.0

create your Controller and with a construct method inject our service as below;

```php
class TryOtoController
{
    public $service;

    public function __construct()
    {
        $this->service = new TryotoService;
    }
    
    /// your code below

```

the constructed service will automatically generate for you the token instance and cache id for 58 minutes by default.
the token is valid for only 1 hour in official documents, so I hardcoded that value for some certain reasons to 58 minutes fixed. you can change it from config file for your needs


## Usage;
some set of samples of usages in controller, you can adjust them in the way of your needs;

```shell
 public function auth()
    {
        return $this->service->authorize();
    }

    public function cancelOrder($orderId = 'OID-60774-1001')
    {
        return $this->service->cancelOrder($orderId);
    }

    public function orderDetail($orderId = 'OID-60774-1001')
    {
        return $this->service->orderDetail($orderId);
    }

    public function orderList()
    {
        return $this->service->listOrders();
    }


    public function createOrder($order)
    {
        return $this->dataSet($order);
    }

    public function setWebhook()
    {
        return (new TryotoService)->setWebhook();
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
            "amount" => (double)$order['amount'],
            "amount_due" => 0,
            "customsValue" => "12",
            "customsCurrency" => "TRY",
            "shippingAmount" => 20,
            "subtotal" => (double)$order['sub_total'],
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
                "rowTotal" => (double)$single['price'] * $single['quantity'],
                "taxAmount" => $single['taxAmount'],
                "quantity" => $single['quantity'],
                "serialnumber" => $single['serialnumber'],
                "sku" => $single['sku'],
                "image" => $single['image'],
            ];
        }
        return $result;
    }


    public function listenWebhook(Request $request)
    {
        $orderId = $request->get('orderId');
        $statusCode = $request->get('status');

        $trackingNumber = $request->get('trackingNumber');
        $brandedTrackingURL = $request->get('brandedTrackingURL');
        $driverName = $request->get('driverName');
        $driverEmail = $request->get('driverEmail');
        $timestamp = $request->get('timestamp');


        if (!$statusCode || !$orderId) {
            return false;
        }

        // here is your code for handling the webhook response

    }

```

## Credits

- [Selim Görmüş](http://siberfx.com) - Lead Software Engineer & Lead Maintainer;
