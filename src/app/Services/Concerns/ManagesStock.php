<?php

namespace Siberfx\LaravelTryoto\app\Services\Concerns;

/**
 * "Stock Management" section of the OTO API.
 */
trait ManagesStock
{
    /**
     * POST /rest/v2/updateStockQuantity
     *
     * @param  array  $parameters  actionType, locationCode, sku, qty
     */
    public function updateStockQuantity(array $parameters)
    {
        return $this->call('POST', '/rest/v2/updateStockQuantity', $parameters);
    }


    /**
     * GET /rest/v2/checkInventoryStock — stock per location.
     *
     * @param  string|array  $skus
     */
    public function checkInventoryStock($skus)
    {
        return $this->call('GET', '/rest/v2/checkInventoryStock', query: ['sku' => implode(',', (array) $skus)]);
    }


    /**
     * GET /rest/v2/checkGlobalStock — total stock across all locations.
     *
     * @param  string|array  $skus
     */
    public function checkGlobalStock($skus)
    {
        return $this->call('GET', '/rest/v2/checkGlobalStock', query: ['sku' => implode(',', (array) $skus)]);
    }


    /**
     * POST /rest/v2/createInventoryOrder — inbound/outbound inventory movement.
     *
     * @param  array  $parameters  action, locationCode, binLocationName, orderDate, deliveryDate, waybillNumber,
     *                             description, items
     */
    public function createInventoryOrder(array $parameters)
    {
        return $this->call('POST', '/rest/v2/createInventoryOrder', $parameters);
    }


    /**
     * POST /rest/v2/updatePackingStatus
     */
    public function updatePackingStatus($orderId, string $packingStatus)
    {
        return $this->call('POST', '/rest/v2/updatePackingStatus', [
            'orderId' => $orderId,
            'packingStatus' => $packingStatus,
        ]);
    }


    /**
     * POST /rest/v2/getPackingOrders — orders ready for packing in a warehouse.
     */
    public function getPackingOrders(string $warehouseCode)
    {
        return $this->call('POST', '/rest/v2/getPackingOrders', ['warehouseCode' => $warehouseCode]);
    }


    /**
     * POST /rest/v2/availableStoresForPickup
     */
    public function availableStoresForPickup(array $items, ?string $selectedCity = null)
    {
        return $this->call('POST', '/rest/v2/availableStoresForPickup', self::withoutEmpty([
            'items' => $items,
            'selectedCity' => $selectedCity,
        ]));
    }


    /**
     * POST /rest/v2/availableCitiesForPickup
     */
    public function availableCitiesForPickup(array $parameters = [])
    {
        return $this->call('POST', '/rest/v2/availableCitiesForPickup', $parameters);
    }
}
