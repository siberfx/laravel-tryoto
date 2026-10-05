<?php

namespace Siberfx\LaravelTryoto\app\Services\Concerns;

/**
 * "Authorization", "Account" and "Transactions" sections of the OTO API.
 */
trait ManagesAccount
{
    /**
     * GET /rest/v2/healthCheck
     */
    public function healthCheck()
    {
        return $this->call('GET', '/rest/v2/healthCheck');
    }


    /**
     * GET /rest/v2/accountInfo
     */
    public function accountInfo()
    {
        return $this->call('GET', '/rest/v2/accountInfo');
    }


    /**
     * POST /rest/v2/buyCredit — returns a payment link for the requested amount.
     */
    public function buyCredit($amount)
    {
        return $this->call('POST', '/rest/v2/buyCredit', ['amount' => $amount]);
    }


    /**
     * POST /rest/v2/requestMobileVerification
     */
    public function requestMobileVerification(string $phone)
    {
        return $this->call('POST', '/rest/v2/requestMobileVerification', ['phone' => $phone]);
    }


    /**
     * POST /rest/v2/verifyMobileNumber
     *
     * @param  string  $token  token returned by requestMobileVerification()
     */
    public function verifyMobileNumber(string $phone, string $code, string $token)
    {
        return $this->call('POST', '/rest/v2/verifyMobileNumber', [
            'phone' => $phone,
            'code' => $code,
            'token' => $token,
        ]);
    }


    /**
     * POST /rest/v2/getShippingPriceTransactionsList
     *
     * @param  array  $parameters  shipmentId, orderId
     */
    public function shippingPriceTransactions(array $parameters)
    {
        return $this->call('POST', '/rest/v2/getShippingPriceTransactionsList', $parameters);
    }


    /**
     * GET /rest/v2/creditTransactions
     *
     * @param  array  $filters  perPage (max 100), page, minDate, maxDate (yyyy-mm-dd), orderId
     */
    public function creditTransactions(array $filters = [])
    {
        return $this->call('GET', '/rest/v2/creditTransactions', query: $filters);
    }


    /**
     * GET /rest/v2/shipmentTransactions
     *
     * @param  array  $filters  perPage, page, minDate, maxDate (yyyy-mm-dd)
     */
    public function shipmentTransactions(array $filters = [])
    {
        return $this->call('GET', '/rest/v2/shipmentTransactions', query: $filters);
    }


    /**
     * GET /rest/v2/codTransactions — COD wallet balance and movements.
     *
     * @param  array  $filters  perPage (max 100), page, minDate, maxDate (yyyy-mm-dd)
     */
    public function codTransactions(array $filters = [])
    {
        return $this->call('GET', '/rest/v2/codTransactions', query: $filters);
    }
}
