<?php

return [

    'titles' => [
        'order_status' => ':icon Order :order is :status',
        'shipment_error' => '⚠️ Shipment error for order :order',
        'new_order' => '🆕 New order :order',
        'wallet_transaction' => '💳 Wallet transaction :amount',
    ],

    'fields' => [
        'status' => 'Status',
        'carrier_status' => 'Carrier status',
        'return_status' => 'Return status',
        'carrier' => 'Carrier',
        'tracking_number' => 'Tracking number',
        'driver' => 'Driver',
        'failed_attempt' => 'Failed attempt',
        'note' => 'Note',
        'error' => 'Error',
        'code' => 'Code',
        'carrier_response' => 'Carrier response',
        'total' => 'Total',
        'payment' => 'Payment',
        'customer' => 'Customer',
        'sales_channel' => 'Sales channel',
        'brand' => 'Brand',
        'items' => 'Items',
        'order' => 'Order',
        'type' => 'Type',
        'charge_type' => 'Charge type',
        'description' => 'Description',
        'remaining_balance' => 'Remaining balance',
    ],

    'links' => [
        'open' => 'Open',
        'track_shipment' => 'Track shipment',
    ],

    'boolean' => [
        'yes' => 'yes',
        'no' => 'no',
    ],

    // OTO order status codes, shown in message titles. Unknown codes are shown as sent.
    'statuses' => [
        'updated' => 'updated',
        'new' => 'new',
        'assignedToWarehouse' => 'assigned to warehouse',
        'shipmentCreated' => 'shipment created',
        'shipmentProcessing' => 'shipment processing',
        'searchingDriver' => 'searching for a driver',
        'pickedUp' => 'picked up',
        'shipmentInProgress' => 'in progress',
        'inTransit' => 'in transit',
        'outForDelivery' => 'out for delivery',
        'delivered' => 'delivered',
        'onHold' => 'on hold',
        'shipmentOnHold' => 'shipment on hold',
        'canceled' => 'canceled',
        'cancelled' => 'canceled',
        'shipmentCanceled' => 'shipment canceled',
        'returned' => 'returned',
        'returnShipmentProcessing' => 'return processing',
        'reverseShipment' => 'reverse shipment',
        'reverseShipmentCreated' => 'reverse shipment created',
        'reverseShipmentProcessing' => 'reverse shipment processing',
        'reverseShipmentOnHold' => 'reverse shipment on hold',
        'reverseShipmentCanceled' => 'reverse shipment canceled',
        'reverseReturned' => 'returned to sender',
        'lost' => 'lost',
        'damaged' => 'damaged',
    ],

];
