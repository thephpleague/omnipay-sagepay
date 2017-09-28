<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Server Authorize Response
 */
class ServerAuthorizeResponse extends Response
{
    public function isSuccessful()
    {
        return false;
    }

    public function isRedirect()
    {
        return in_array(
            $this->getStatus(),
            array(static::SAGEPAY_STATUS_OK, static::SAGEPAY_STATUS_OK_REPEATED)
        );
    }

    public function getRedirectUrl()
    {
        return $this->getDataItem('NextURL');
    }

    public function getRedirectMethod()
    {
        return 'GET';
    }

    public function getRedirectData()
    {
        return null;
    }
}
