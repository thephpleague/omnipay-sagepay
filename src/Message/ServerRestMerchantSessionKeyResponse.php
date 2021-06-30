<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay REST Server Merchant Session Key  Response
 */
class ServerRestMerchantSessionKeyResponse extends Response
{
    /**
     * The initial Server response is never complete without
     * redirecting the user.
     *
     * @return bool false
     */
    public function isSuccessful()
    {
        return $this->getMerchantSessionKey() ?? false;
    }

    /**
     * @return string|null MSK if present
     */
    public function getMerchantSessionKey()
    {
        return $this->getDataItem('merchantSessionKey');
    }
}
