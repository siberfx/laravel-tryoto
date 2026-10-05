<?php

namespace Siberfx\LaravelTryoto\app\Services\Concerns;

/**
 * "Orders" and "Customer Notifications" sections of the OTO API.
 */
trait ManagesOrders
{
    /**
     * GET /rest/v2/orders
     *
     * @param  array  $filters  status, minDate, maxDate (yyyy-mm-dd), perPage (max 100)
     */
    public function listOrders(int $page = 1, array $filters = [])
    {
        $query = array_merge(['perPage' => 100, 'page' => $page], $filters);

        return $this->request('GET', '/rest/v2/orders', query: $query)->object();
    }


    /**
     * GET /rest/v2/orderDetails
     */
    public function orderDetail($orderId = null)
    {
        if (empty($orderId)) {
            return [];
        }

        return $this->request('GET', '/rest/v2/orderDetails', query: ['orderId' => $orderId])->object();
    }


    /**
     * POST /rest/v2/createOrder
     */
    public function createOrder($body)
    {
        return $this->call('POST', '/rest/v2/createOrder', $body);
    }


    /**
     * POST /rest/v2/updateOrder
     */
    public function updateOrder($body)
    {
        return $this->call('POST', '/rest/v2/updateOrder', $body);
    }


    /**
     * POST /rest/v2/cancelOrder
     */
    public function cancelOrder($orderId)
    {
        if (empty($orderId)) {
            return [];
        }

        return $this->call('POST', '/rest/v2/cancelOrder', ['orderId' => $orderId]);
    }


    /**
     * POST /rest/v2/holdOrder
     */
    public function holdOrder($orderId, string $reason = '', string $reasonLang = 'en')
    {
        if (empty($orderId)) {
            return [];
        }

        return $this->call('POST', '/rest/v2/holdOrder', [
            'orderId' => $orderId,
            'onHoldReason' => $reason,
            'onHoldReasonLang' => $reasonLang,
        ]);
    }


    /**
     * POST /rest/v2/unHoldOrder
     */
    public function unHoldOrder($orderId)
    {
        if (empty($orderId)) {
            return [];
        }

        return $this->call('POST', '/rest/v2/unHoldOrder', ['orderId' => $orderId]);
    }


    /**
     * POST /rest/v2/updateOrderStatus
     *
     * @param  string|array  $orderIds  comma separated string or array of order ids
     */
    public function updateOrderStatus($orderIds, string $status, string $description = '', ?string $date = null)
    {
        if (empty($orderIds) || $status === '') {
            return [];
        }

        return $this->call('POST', '/rest/v2/updateOrderStatus', self::withoutEmpty([
            'orderIds' => $orderIds,
            'status' => $status,
            'description' => $description,
            'date' => $date,
        ]));
    }


    /**
     * POST /rest/v2/checkOrderAvailability
     */
    public function checkOrderAvailability($orderId, array $ruleIds = [])
    {
        return $this->call('POST', '/rest/v2/checkOrderAvailability', self::withoutEmpty([
            'orderId' => $orderId,
            'ruleIds' => $ruleIds ?: null,
        ]));
    }


    /**
     * GET /rest/v2/orders/{orderId}/customer-notifications
     */
    public function customerNotifications($orderId)
    {
        return $this->call('GET', '/rest/v2/orders/' . rawurlencode((string) $orderId) . '/customer-notifications');
    }
}
