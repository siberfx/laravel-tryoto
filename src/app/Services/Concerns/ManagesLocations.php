<?php

namespace Siberfx\LaravelTryoto\app\Services\Concerns;

/**
 * Coverage, cities and delivery-estimation endpoints ("Carrier Integrations" and
 * "National Address" sections of the OTO API).
 */
trait ManagesLocations
{
    /**
     * POST /rest/v2/checkCoverage
     */
    public function checkCoverage(array $parameters)
    {
        return $this->call('POST', '/rest/v2/checkCoverage', $parameters);
    }


    /**
     * POST /rest/v2/availableCities
     */
    public function availableCities(?int $limit = null)
    {
        return $this->call('POST', '/rest/v2/availableCities', self::withoutEmpty(['limit' => $limit]));
    }


    /**
     * POST /rest/v2/availableTimeslots
     *
     * @param  array  $parameters  serviceType, packageSize, lat, lon
     */
    public function availableTimeslots(array $parameters)
    {
        return $this->call('POST', '/rest/v2/availableTimeslots', $parameters);
    }


    /**
     * POST /rest/v2/getCities
     *
     * @param  string  $country  ISO country code, e.g. "SA"
     */
    public function getCities(string $country, int $page = 1, int $perPage = 100)
    {
        return $this->call('POST', '/rest/v2/getCities', [
            'country' => $country,
            'perPage' => $perPage,
            'page' => $page,
        ]);
    }


    /**
     * POST /rest/v2/getDeliveryEstimation
     *
     * @param  array  $parameters  calculationData, deliveryCompanySettingsId, slaMethodType
     */
    public function getDeliveryEstimation(array $parameters)
    {
        return $this->call('POST', '/rest/v2/getDeliveryEstimation', $parameters);
    }


    /**
     * POST /rest/v2/aiEstimatedDeliveryDates
     *
     * @param  array  $parameters  weight, originCity, destinationCity, height, width, length, includeEstimatedDates
     */
    public function aiEstimatedDeliveryDates(array $parameters)
    {
        return $this->call('POST', '/rest/v2/aiEstimatedDeliveryDates', $parameters);
    }


    /**
     * POST /rest/v2/getNationalAddressFromShortCode — Saudi national address lookup.
     */
    public function nationalAddressFromShortCode(string $shortAddressCode)
    {
        return $this->call('POST', '/rest/v2/getNationalAddressFromShortCode', ['shortAddressCode' => $shortAddressCode]);
    }
}
