<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay REST Server Complete Response
 */
class ServerRestCompleteResponse extends RestResponse
{
    /**
     *
     * @return bool false
     */
    public function isSuccessful()
    {
        return strtoupper($this->get3DSecureStatus() ?? $this->getStatus()) === static::SAGEPAY_STATUS_AUTHENTICATED;
    }
}
