<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Direct Purchase Request
 */
class DirectPurchaseRequest extends DirectAuthorizeRequest
{
    public function getService()
    {
        return static::SERVICE_DIRECT_REGISTER; 
    }
}
