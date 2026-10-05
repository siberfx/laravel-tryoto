<?php

use Siberfx\LaravelTryoto\app\Services\TryotoService;

/*
 * Every wrapper method must hit the documented OTO endpoint with the documented HTTP method,
 * query string and JSON body. See https://apis.tryoto.com/
 */

dataset('endpoints', [
    // Account & transactions
    'healthCheck' => [fn (TryotoService $oto) => $oto->healthCheck(), 'GET', '/rest/v2/healthCheck', [], []],
    'accountInfo' => [fn (TryotoService $oto) => $oto->accountInfo(), 'GET', '/rest/v2/accountInfo', [], []],
    'buyCredit' => [fn (TryotoService $oto) => $oto->buyCredit(500), 'POST', '/rest/v2/buyCredit', [], ['amount' => 500]],
    'requestMobileVerification' => [fn (TryotoService $oto) => $oto->requestMobileVerification('966500000000'), 'POST', '/rest/v2/requestMobileVerification', [], ['phone' => '966500000000']],
    'verifyMobileNumber' => [fn (TryotoService $oto) => $oto->verifyMobileNumber('966500000000', '1234', 'tkn'), 'POST', '/rest/v2/verifyMobileNumber', [], ['phone' => '966500000000', 'code' => '1234', 'token' => 'tkn']],
    'shippingPriceTransactions' => [fn (TryotoService $oto) => $oto->shippingPriceTransactions(['orderId' => '1']), 'POST', '/rest/v2/getShippingPriceTransactionsList', [], ['orderId' => '1']],
    'creditTransactions' => [fn (TryotoService $oto) => $oto->creditTransactions(['page' => 2, 'minDate' => '2026-01-01', 'orderId' => '']), 'GET', '/rest/v2/creditTransactions', ['page' => '2', 'minDate' => '2026-01-01'], []],
    'shipmentTransactions' => [fn (TryotoService $oto) => $oto->shipmentTransactions(['perPage' => 10]), 'GET', '/rest/v2/shipmentTransactions', ['perPage' => '10'], []],
    'codTransactions' => [fn (TryotoService $oto) => $oto->codTransactions(['maxDate' => '2026-02-01']), 'GET', '/rest/v2/codTransactions', ['maxDate' => '2026-02-01'], []],

    // Orders
    'createOrder' => [fn (TryotoService $oto) => $oto->createOrder(['orderId' => '1']), 'POST', '/rest/v2/createOrder', [], ['orderId' => '1']],
    'updateOrder' => [fn (TryotoService $oto) => $oto->updateOrder(['orderId' => '1', 'amount' => 10]), 'POST', '/rest/v2/updateOrder', [], ['orderId' => '1', 'amount' => 10]],
    'cancelOrder' => [fn (TryotoService $oto) => $oto->cancelOrder('1'), 'POST', '/rest/v2/cancelOrder', [], ['orderId' => '1']],
    'holdOrder' => [fn (TryotoService $oto) => $oto->holdOrder('1', 'stock', 'ar'), 'POST', '/rest/v2/holdOrder', [], ['orderId' => '1', 'onHoldReason' => 'stock', 'onHoldReasonLang' => 'ar']],
    'unHoldOrder' => [fn (TryotoService $oto) => $oto->unHoldOrder('1'), 'POST', '/rest/v2/unHoldOrder', [], ['orderId' => '1']],
    'updateOrderStatus' => [fn (TryotoService $oto) => $oto->updateOrderStatus(['1', '2'], 'delivered'), 'POST', '/rest/v2/updateOrderStatus', [], ['orderIds' => ['1', '2'], 'status' => 'delivered']],
    'updateOrderStatus with extras' => [fn (TryotoService $oto) => $oto->updateOrderStatus('1', 'delivered', 'note', '2026-10-05 10:00:00'), 'POST', '/rest/v2/updateOrderStatus', [], ['orderIds' => '1', 'status' => 'delivered', 'description' => 'note', 'date' => '2026-10-05 10:00:00']],
    'checkOrderAvailability' => [fn (TryotoService $oto) => $oto->checkOrderAvailability('1', [3]), 'POST', '/rest/v2/checkOrderAvailability', [], ['orderId' => '1', 'ruleIds' => [3]]],
    'customerNotifications' => [fn (TryotoService $oto) => $oto->customerNotifications('OID 1'), 'GET', '/rest/v2/orders/OID%201/customer-notifications', [], []],

    // Shipping prices & shipments
    'checkOTODeliveryFee' => [fn (TryotoService $oto) => $oto->checkOTODeliveryFee(['weight' => 1]), 'POST', '/rest/v2/checkOTODeliveryFee', [], ['weight' => 1]],
    'checkDeliveryFee' => [fn (TryotoService $oto) => $oto->checkDeliveryFee(['weight' => 1, 'totalDue' => 0]), 'POST', '/rest/v2/checkDeliveryFee', [], ['weight' => 1, 'totalDue' => 0]],
    'getDeliveryFee' => [fn (TryotoService $oto) => $oto->getDeliveryFee('1'), 'POST', '/rest/v2/getDeliveryFee', [], ['orderId' => '1']],
    'getDeliveryOptions' => [fn (TryotoService $oto) => $oto->getDeliveryOptions(['city' => 'Riyadh']), 'GET', '/rest/v2/getDeliveryOptions', ['city' => 'Riyadh'], []],
    'createShipment' => [fn (TryotoService $oto) => $oto->createShipment('1', 564, ['serviceType' => 'express']), 'POST', '/rest/v2/createShipment', [], ['orderId' => '1', 'deliveryOptionId' => 564, 'serviceType' => 'express']],
    'cancelShipment' => [fn (TryotoService $oto) => $oto->cancelShipment('1', 9), 'POST', '/rest/v2/cancelShipment', [], ['orderId' => '1', 'shipmentId' => 9]],
    'cancelShipment without shipmentId' => [fn (TryotoService $oto) => $oto->cancelShipment('1'), 'POST', '/rest/v2/cancelShipment', [], ['orderId' => '1']],

    // Printing & tracking
    'printAwb' => [fn (TryotoService $oto) => $oto->printAwb('OID-1'), 'GET', '/rest/v2/print/OID-1', [], []],
    'printAwb reverse' => [fn (TryotoService $oto) => $oto->printAwb('OID-1', ['printReverseShipment' => true, 'internationalProforma' => false]), 'GET', '/rest/v2/print/OID-1', ['printReverseShipment' => 'true', 'internationalProforma' => 'false'], []],
    'printLabel' => [fn (TryotoService $oto) => $oto->printLabel('1', 'proforma', false), 'POST', '/rest/v2/printLabel', [], ['orderId' => '1', 'type' => 'proforma', 'returnURL' => false]],
    'orderStatus' => [fn (TryotoService $oto) => $oto->orderStatus('1', ['labelType' => 'ZPL']), 'POST', '/rest/v2/orderStatus', [], ['orderId' => '1', 'labelType' => 'ZPL']],
    'orderHistory' => [fn (TryotoService $oto) => $oto->orderHistory(['a' => '1', 'b' => '2']), 'POST', '/rest/v2/orderHistory', [], ['orderIds' => ['1', '2']]],
    'trackShipment' => [fn (TryotoService $oto) => $oto->trackShipment(['trackingNumber' => 'T1']), 'POST', '/rest/v2/trackShipment', [], ['trackingNumber' => 'T1']],

    // Returns
    'createReturnShipment' => [fn (TryotoService $oto) => $oto->createReturnShipment(['orderId' => '1']), 'POST', '/rest/v2/createReturnShipment', [], ['orderId' => '1']],
    'getReturnLink' => [fn (TryotoService $oto) => $oto->getReturnLink('1'), 'POST', '/rest/v2/getReturnLink', [], ['orderId' => '1']],
    'getReturnDetails' => [fn (TryotoService $oto) => $oto->getReturnDetails('1'), 'POST', '/rest/v2/getReturnDetails', [], ['orderId' => '1']],
    'triggerReturnSms' => [fn (TryotoService $oto) => $oto->triggerReturnSms('1'), 'POST', '/rest/v2/triggerReturnSms', [], ['orderId' => '1']],

    // Pickup locations
    'createPickupLocation' => [fn (TryotoService $oto) => $oto->createPickupLocation(['code' => 'wh']), 'POST', '/rest/v2/createPickupLocation', [], ['code' => 'wh']],
    'updatePickupLocation' => [fn (TryotoService $oto) => $oto->updatePickupLocation(['code' => 'wh', 'status' => 'active']), 'POST', '/rest/v2/updatePickupLocation', [], ['code' => 'wh', 'status' => 'active']],
    'getPickupLocationList' => [fn (TryotoService $oto) => $oto->getPickupLocationList(['status' => 'active']), 'GET', '/rest/v2/getPickupLocationList', ['status' => 'active'], []],
    'pickupLocationWorkingHours' => [fn (TryotoService $oto) => $oto->pickupLocationWorkingHours([['code' => 'wh']]), 'POST', '/rest/v2/pickupLocationWorkingHours', [], ['pickupLocations' => [['code' => 'wh']]]],

    // Webhooks
    'listWebhooks' => [fn (TryotoService $oto) => $oto->listWebhooks(), 'GET', '/rest/v2/webhook', [], []],
    'listWebhooks by id' => [fn (TryotoService $oto) => $oto->listWebhooks(59), 'GET', '/rest/v2/webhook', ['id' => '59'], []],
    'deleteWebhook' => [fn (TryotoService $oto) => $oto->deleteWebhook(3), 'DELETE', '/rest/v2/webhook', ['id' => '3'], []],

    // Products & boxes
    'createProduct' => [fn (TryotoService $oto) => $oto->createProduct(['sku' => 'S1']), 'POST', '/rest/v2/createProduct', [], ['sku' => 'S1']],
    'productList' => [fn (TryotoService $oto) => $oto->productList(2, 25), 'POST', '/rest/v2/productList', [], ['pageSize' => 25, 'currentPage' => 2]],
    'addBox' => [fn (TryotoService $oto) => $oto->addBox('medium', 30, 20, 10), 'POST', '/rest/v2/addBox', [], ['name' => 'medium', 'length' => 30, 'width' => 20, 'height' => 10]],
    'updateBox' => [fn (TryotoService $oto) => $oto->updateBox('medium', 31, 21, 11), 'POST', '/rest/v2/updateBox', [], ['name' => 'medium', 'length' => 31, 'width' => 21, 'height' => 11]],
    'getBox' => [fn (TryotoService $oto) => $oto->getBox('medium'), 'GET', '/rest/v2/getBox', ['name' => 'medium'], []],
    'getBox all' => [fn (TryotoService $oto) => $oto->getBox(), 'GET', '/rest/v2/getBox', [], []],

    // Stock
    'updateStockQuantity' => [fn (TryotoService $oto) => $oto->updateStockQuantity(['actionType' => 'adjust', 'qty' => 5]), 'POST', '/rest/v2/updateStockQuantity', [], ['actionType' => 'adjust', 'qty' => 5]],
    'checkInventoryStock' => [fn (TryotoService $oto) => $oto->checkInventoryStock(['SG1', 'SG2']), 'GET', '/rest/v2/checkInventoryStock', ['sku' => 'SG1,SG2'], []],
    'checkGlobalStock' => [fn (TryotoService $oto) => $oto->checkGlobalStock('SG1'), 'GET', '/rest/v2/checkGlobalStock', ['sku' => 'SG1'], []],
    'createInventoryOrder' => [fn (TryotoService $oto) => $oto->createInventoryOrder(['action' => 'inbound']), 'POST', '/rest/v2/createInventoryOrder', [], ['action' => 'inbound']],
    'updatePackingStatus' => [fn (TryotoService $oto) => $oto->updatePackingStatus('1', 'packed'), 'POST', '/rest/v2/updatePackingStatus', [], ['orderId' => '1', 'packingStatus' => 'packed']],
    'getPackingOrders' => [fn (TryotoService $oto) => $oto->getPackingOrders('wh'), 'POST', '/rest/v2/getPackingOrders', [], ['warehouseCode' => 'wh']],
    'availableStoresForPickup' => [fn (TryotoService $oto) => $oto->availableStoresForPickup([['sku' => 'S1']], 'Riyadh'), 'POST', '/rest/v2/availableStoresForPickup', [], ['items' => [['sku' => 'S1']], 'selectedCity' => 'Riyadh']],
    'availableCitiesForPickup' => [fn (TryotoService $oto) => $oto->availableCitiesForPickup(), 'POST', '/rest/v2/availableCitiesForPickup', [], []],

    // Coverage, cities & addresses
    'checkCoverage' => [fn (TryotoService $oto) => $oto->checkCoverage(['city' => 'Riyadh']), 'POST', '/rest/v2/checkCoverage', [], ['city' => 'Riyadh']],
    'availableCities' => [fn (TryotoService $oto) => $oto->availableCities(50), 'POST', '/rest/v2/availableCities', [], ['limit' => 50]],
    'availableTimeslots' => [fn (TryotoService $oto) => $oto->availableTimeslots(['packageSize' => 'small']), 'POST', '/rest/v2/availableTimeslots', [], ['packageSize' => 'small']],
    'getCities' => [fn (TryotoService $oto) => $oto->getCities('SA', 2, 50), 'POST', '/rest/v2/getCities', [], ['country' => 'SA', 'perPage' => 50, 'page' => 2]],
    'getDeliveryEstimation' => [fn (TryotoService $oto) => $oto->getDeliveryEstimation(['slaMethodType' => 'x']), 'POST', '/rest/v2/getDeliveryEstimation', [], ['slaMethodType' => 'x']],
    'aiEstimatedDeliveryDates' => [fn (TryotoService $oto) => $oto->aiEstimatedDeliveryDates(['weight' => 1]), 'POST', '/rest/v2/aiEstimatedDeliveryDates', [], ['weight' => 1]],
    'nationalAddressFromShortCode' => [fn (TryotoService $oto) => $oto->nationalAddressFromShortCode('RGUC8214'), 'POST', '/rest/v2/getNationalAddressFromShortCode', [], ['shortAddressCode' => 'RGUC8214']],
]);

it('calls the documented endpoint', function (Closure $call, string $method, string $path, array $query, array $body) {
    $this->fakeOto(['success' => true, 'ok' => 'yes']);

    $result = $call($this->oto());

    $request = lastApiRequest();
    $url = parse_url($request->url());
    parse_str($url['query'] ?? '', $sentQuery);

    expect($request->method())->toBe($method)
        ->and($url['scheme'] . '://' . $url['host'])->toBe(self::BASE_URL)
        ->and($url['path'])->toBe($path)
        ->and($sentQuery)->toBe($query)
        ->and(jsonBody($request))->toBe($body)
        ->and($request->header('Authorization'))->toBe(['Bearer access-token'])
        ->and($request->header('Accept'))->toBe(['application/json'])
        ->and($result)->toBe(['success' => true, 'ok' => 'yes']);
})->with('endpoints');
