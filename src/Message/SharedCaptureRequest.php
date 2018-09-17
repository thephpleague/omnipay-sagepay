<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Direct Capture Request
 */
class SharedCaptureRequest extends AbstractRequest
{
    protected $action = 'RELEASE';

    /**
     * @return array The message body data.
     */
    public function getData()
    {
        $this->validate('amount', 'relatedTransactionId', 'vpsTxId', 'securityKey', 'txAuthNo');

        $data = $this->getBaseData();

        $data['ReleaseAmount'] = $this->getAmount();

        // Reference to the transaction to capture.
        // Supplied individually, or as a JSON transactionReference

        $data['VendorTxCode'] = $this->getRelatedTransactionId();
        $data['VPSTxId'] = $this->getVPSTxId();
        $data['SecurityKey'] = $this->getSecurityKey();
        $data['TxAuthNo'] = $this->getTxAuthNo();

        return $data;
    }
}
