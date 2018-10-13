<?php

namespace Omnipay\SagePay\Message\Form;

/**
 * Sage Pay Form Complete Purchase Response.
 */

class CompletePurchaseRequest extends CompleteAuthorizeRequest
{
    /**
     * @return string the transaction type
     */
    public function getTxType()
    {
        return static::TXTYPE_PAYMENT;
    }
}
