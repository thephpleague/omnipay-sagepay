<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Server Purchase Request
 */
class ServerPurchaseRequest extends ServerAuthorizeRequest
{
    public function getData()
    {
        $data = parent::getData();

        return $data;
    }

    /**
     * @return string the transaction type
     */
    public function getTxType()
    {
        return $this->getRelatedTransactionId() ? static::TXTYPE_REPEAT : static::TXTYPE_PAYMENT;
    }
}
