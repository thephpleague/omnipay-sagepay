<?php

namespace Omnipay\SagePay\Message\Form;

/**
 * Sage Pay Direct Purchase Request
 */
class PurchaseRequest extends AuthorizeRequest
{
    public function getTxType()
    {
        return static::TXTYPE_PAYMENT;
    }
}
