<?php

namespace Siberfx\LaravelTryoto\app\Services\Concerns;

/**
 * "Pickup Locations" section of the OTO API.
 */
trait ManagesPickupLocations
{
    /**
     * POST /rest/v2/createPickupLocation
     *
     * @param  array  $location  type (warehouse|branch), code, name, mobile, address, contactName, contactEmail,
     *                           lat, lon, city, country, street, district, postcode, servingRadius, brandName, ...
     */
    public function createPickupLocation(array $location)
    {
        return $this->call('POST', '/rest/v2/createPickupLocation', $location);
    }


    /**
     * POST /rest/v2/updatePickupLocation — identified by "code".
     */
    public function updatePickupLocation(array $location)
    {
        return $this->call('POST', '/rest/v2/updatePickupLocation', $location);
    }


    /**
     * GET /rest/v2/getPickupLocationList
     *
     * @param  array  $filters  minDate, maxDate (yyyy-mm-dd), status
     */
    public function getPickupLocationList(array $filters = [])
    {
        return $this->call('GET', '/rest/v2/getPickupLocationList', query: $filters);
    }


    /**
     * POST /rest/v2/pickupLocationWorkingHours — add or update working hours.
     *
     * @param  array  $pickupLocations  list of locations with their working hours, as documented
     */
    public function pickupLocationWorkingHours(array $pickupLocations)
    {
        return $this->call('POST', '/rest/v2/pickupLocationWorkingHours', ['pickupLocations' => $pickupLocations]);
    }
}
