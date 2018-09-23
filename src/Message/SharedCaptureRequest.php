<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Direct Capture Request.
 * Performs a release or an authorise, depending on the
 * setting 'useAuthenticate'.
 */
class SharedCaptureRequest extends AbstractRequest
{
    /**
     * @return string the transaction type
     */
    public function getTxType()
    {
        if ($this->getUseAuthenticate()) {
            return static::TXTYPE_AUTHORISE;
        } else {
            return static::TXTYPE_RELEASE;
        }
    }

    /**
     * @return array The message body data.
     */
    public function getData()
    {
        $this->validate('amount', 'relatedTransactionId', 'vpsTxId', 'securityKey');

        $data = $this->getBaseData();

        if ($this->getUseAuthenticate()) {
            $this->validate('transactionId', 'description');

            $data['Amount'] = $this->getAmount();
            $data['Description'] = $this->getDescription();

            $data['VendorTxCode'] = $this->getTransactionId();

            $data['RelatedVendorTxCode'] = $this->getRelatedTransactionId();
            $data['RelatedVPSTxId'] = $this->getVPSTxId();
            $data['RelatedSecurityKey'] = $this->getSecurityKey();

            // The documentation (2015) says this is required. But it can't be, because
            // we won't have it for authenticate, as the bank has not been visited.
            // We will follow the spec though, but treat it as optional here.

            if ($this->getTxAuthNo() !== null) {
                $data['RelatedTxAuthNo'] = $this->getTxAuthNo();
            }
        } else {
            $this->validate('txAuthNo');

            $data['ReleaseAmount'] = $this->getAmount();

            // Reference to the transaction to capture.
            // Supplied individually, or as a JSON transactionReference

            $data['VendorTxCode'] = $this->getRelatedTransactionId();

            $data['VPSTxId'] = $this->getVPSTxId();
            $data['SecurityKey'] = $this->getSecurityKey();
            $data['TxAuthNo'] = $this->getTxAuthNo();
        }

        return $data;
    }
}
