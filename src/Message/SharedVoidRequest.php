<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Shared Void Request
 */
class SharedVoidRequest extends AbstractRequest
{
    public function getTxType()
    {
        return static::TXTYPE_VOID;
    }

    public function getData()
    {
        $this->validate('relatedTransactionId', 'vpsTxId', 'securityKey', 'txAuthNo');

        $data = $this->getBaseData();

        // Reference to the transaction to void.
        // Supplied individually, or as a JSON transactionReference

        $data['VendorTxCode'] = $this->getRelatedTransactionId();
        $data['VPSTxId'] = $this->getVPSTxId();
        $data['SecurityKey'] = $this->getSecurityKey();
        $data['TxAuthNo'] = $this->getTxAuthNo();

        return $data;
    }
}
