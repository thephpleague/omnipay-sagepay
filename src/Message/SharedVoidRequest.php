<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Shared Void Request
 */
class SharedVoidRequest extends AbstractRequest
{
    protected $action = 'VOID';

    public function getData()
    {
        $this->validate('transactionReference');
        $reference = json_decode($this->getTransactionReference(), true);

        $data = $this->getBaseData();

        // Reference to the transaction to void.
        $data['VendorTxCode'] = $reference['VendorTxCode'];
        $data['VPSTxId'] = $reference['VPSTxId'];
        $data['SecurityKey'] = $reference['SecurityKey'];
        $data['TxAuthNo'] = $reference['TxAuthNo'];

        return $data;
    }
}
