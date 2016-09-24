<?php

namespace Omnipay\SagePay\Message;

/**
 * @deprecated Use ServerNotifyRequest via $gateway->acceptNotification()
 */

use Omnipay\Common\Exception\InvalidResponseException;

class ServerTokenRegistrationCompleteRequest extends AbstractRequest
{
    public function getSignature()
    {
        $this->validate('transactionReference');

        $reference = json_decode($this->getTransactionReference(), true);

        // Strip out leading/trailing curly brackets as these are not required in the
        // MD5 signature for some reason (unlike in the standard Server request).
        $vpsTxId = str_replace(array('{', '}'), '', $reference['VPSTxId']);

        // Re-create the VPSSignature
        $signature_string =
            $vpsTxId.
            $reference['VendorTxCode'].
            $this->httpRequest->request->get('Status').
            $this->getVendor().
            $this->httpRequest->request->get('Token').
            $reference['SecurityKey'];

        return md5($signature_string);
    }

    /**
     * Get the POSTed data, checking that the signature is valid.
     */
    public function getData()
    {
        $signature = $this->getSignature();

        if (strtolower($this->httpRequest->request->get('VPSSignature')) !== $signature) {
            throw new InvalidResponseException();
        }

        return $this->httpRequest->request->all();
    }

    public function sendData($data)
    {
        return $this->response = new ServerTokenRegistrationCompleteResponse($this, $data);
    }
}
