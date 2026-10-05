<?php

namespace Siberfx\LaravelTryoto\app\Services\Concerns;

/**
 * "Return Shipments" section of the OTO API.
 */
trait ManagesReturns
{
    /**
     * POST /rest/v2/createReturnShipment
     *
     * @param  array  $parameters  orderId, deliveryOptionId, pickupLocationCode, items
     */
    public function createReturnShipment(array $parameters)
    {
        return $this->call('POST', '/rest/v2/createReturnShipment', $parameters);
    }


    /**
     * POST /rest/v2/getReturnLink
     */
    public function getReturnLink($orderId)
    {
        return $this->call('POST', '/rest/v2/getReturnLink', ['orderId' => $orderId]);
    }


    /**
     * POST /rest/v2/getReturnDetails
     */
    public function getReturnDetails($orderId)
    {
        return $this->call('POST', '/rest/v2/getReturnDetails', ['orderId' => $orderId]);
    }


    /**
     * POST /rest/v2/triggerReturnSms
     */
    public function triggerReturnSms($orderId)
    {
        return $this->call('POST', '/rest/v2/triggerReturnSms', ['orderId' => $orderId]);
    }
}
