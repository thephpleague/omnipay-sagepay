<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Direct Capture Request
 */
class SharedCaptureRequest extends AbstractRequest
{
    protected $action = 'RELEASE';

    public function getData()
    {
        $this->validate('amount', 'transactionReference');
        $reference = json_decode($this->getTransactionReference(), true);

        $data = $this->getBaseData();

        $data['ReleaseAmount'] = $this->getAmount();

        // Reference to the transaction to capture.
        $data['VendorTxCode'] = $reference['VendorTxCode'];
        $data['VPSTxId'] = $reference['VPSTxId'];
        $data['SecurityKey'] = $reference['SecurityKey'];
        $data['TxAuthNo'] = $reference['TxAuthNo'];

        return $data;
    }
}
