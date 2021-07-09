<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay REST Server Purchase Request
 */
class ServerRestRetrieveTransactionRequest extends AbstractRestRequest
{
    protected $method = 'GET';

    public function getService()
    {
        return static::SERVICE_REST_TRANSACTIONS. '/'.$this->getParameter('transactionId'); // temporary
    }

    /**
     * Add the optional token details to the base data.
     *
     * @return array
     */
    public function getData()
    {
        return [];
    }

    /**
     * @param array $data
     * @return ServerRestPurchaseKeyResponse
     */
    protected function createResponse($data)
    {
        return $this->response = new ServerRestRetrieveTransactionResponse($this, $data);
    }

    /**
     * A card token is returned if one has been requested.
     *
     * @return string Currently an md5 format token.
     */
    public function getPaymentMethodData($data = [])
    {
        $data['paymentMethod']['card']['merchantSessionKey'] = $this->getMerchantSessionKey();
        $data['paymentMethod']['card']['cardIdentifier'] = $this->getCardIdentifier();
        return $data;
    }
}
