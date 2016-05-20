<?php

namespace Omnipay\SagePay\Message;

class ServerTokenRegistrationCompleteResponse extends ServerCompleteAuthorizeResponse
{
    public function getTransactionReference()
    {
        $reference = json_decode($this->getRequest()->getTransactionReference(), true);
        $reference['VendorTxCode'] = $this->getRequest()->getTransactionId();

        return json_encode($reference);
    }
}
