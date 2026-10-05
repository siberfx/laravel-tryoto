<?php

namespace Siberfx\LaravelTryoto\app\Services\Concerns;

/**
 * "Shipping Prices", "Shipments", "Print" and "Tracking" sections of the OTO API.
 */
trait ManagesShipments
{
    /**
     * POST /rest/v2/checkOTODeliveryFee — prices using OTO's own carrier contracts.
     *
     * @param  array  $parameters  weight, originCity, destinationCity, height, width, length, checkShippingRule, ...
     */
    public function checkOTODeliveryFee(array $parameters)
    {
        return $this->call('POST', '/rest/v2/checkOTODeliveryFee', $parameters);
    }


    /**
     * POST /rest/v2/checkDeliveryFee — prices using the delivery companies activated on your account.
     *
     * @param  array  $parameters  weight, totalDue, originCity, destinationCity, height, width, length, ...
     */
    public function checkDeliveryFee(array $parameters)
    {
        return $this->call('POST', '/rest/v2/checkDeliveryFee', $parameters);
    }


    /**
     * POST /rest/v2/getDeliveryFee — delivery options and prices for an existing order.
     */
    public function getDeliveryFee($orderId)
    {
        return $this->call('POST', '/rest/v2/getDeliveryFee', ['orderId' => $orderId]);
    }


    /**
     * GET /rest/v2/getDeliveryOptions
     *
     * @param  array  $filters  id, city
     */
    public function getDeliveryOptions(array $filters = [])
    {
        return $this->call('GET', '/rest/v2/getDeliveryOptions', query: $filters);
    }


    /**
     * POST /rest/v2/createShipment
     *
     * @param  array  $extra  any additional documented fields (e.g. serviceType)
     */
    public function createShipment($orderId, $deliveryOptionId, array $extra = [])
    {
        return $this->call('POST', '/rest/v2/createShipment', array_merge([
            'orderId' => $orderId,
            'deliveryOptionId' => $deliveryOptionId,
        ], $extra));
    }


    /**
     * POST /rest/v2/cancelShipment
     */
    public function cancelShipment($orderId, $shipmentId = null)
    {
        return $this->call('POST', '/rest/v2/cancelShipment', self::withoutEmpty([
            'orderId' => $orderId,
            'shipmentId' => $shipmentId,
        ]));
    }


    /**
     * GET /rest/v2/print/{orderId} — returns printAWBURL for the generated AWB.
     *
     * @param  array  $options  internationalProforma, printReverseShipment (booleans)
     */
    public function printAwb($orderId, array $options = [])
    {
        $query = array_map(static fn ($value) => is_bool($value) ? ($value ? 'true' : 'false') : $value, $options);

        return $this->call('GET', '/rest/v2/print/' . rawurlencode((string) $orderId), query: $query);
    }


    /**
     * POST /rest/v2/printLabel
     *
     * @param  string  $type  proforma, awb, orderDetailsTemplate or return-order
     * @param  bool  $returnUrl  true returns a URL instead of the raw document content
     */
    public function printLabel($orderId, string $type = 'awb', bool $returnUrl = true)
    {
        return $this->call('POST', '/rest/v2/printLabel', [
            'orderId' => $orderId,
            'type' => $type,
            'returnURL' => $returnUrl,
        ]);
    }


    /**
     * POST /rest/v2/orderStatus — current status / tracking of an order.
     *
     * @param  array  $extra  e.g. ['labelType' => 'ZPL']
     */
    public function orderStatus($orderId, array $extra = [])
    {
        return $this->call('POST', '/rest/v2/orderStatus', array_merge(['orderId' => $orderId], $extra));
    }


    /**
     * POST /rest/v2/orderHistory
     */
    public function orderHistory(array $orderIds)
    {
        return $this->call('POST', '/rest/v2/orderHistory', ['orderIds' => array_values($orderIds)]);
    }


    /**
     * POST /rest/v2/trackShipment
     *
     * @param  array  $parameters  trackingNumber, deliveryCompanyName, statusHistory, brandName
     */
    public function trackShipment(array $parameters)
    {
        return $this->call('POST', '/rest/v2/trackShipment', $parameters);
    }
}
