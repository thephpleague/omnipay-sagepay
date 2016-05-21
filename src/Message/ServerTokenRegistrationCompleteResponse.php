<?php

namespace Omnipay\SagePay\Message;

class ServerTokenRegistrationCompleteResponse extends ServerCompleteAuthorizeResponse
{
    public function getTransactionReference()
    {
        return $this->request->getTransactionReference();
    }
}
