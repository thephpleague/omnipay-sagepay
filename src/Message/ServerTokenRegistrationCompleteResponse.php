<?php

namespace Omnipay\SagePay\Message;

/**
 * @deprecated Use ServerNotifyRequest via $gateway->acceptNotification()
 */

class ServerTokenRegistrationCompleteResponse extends ServerCompleteAuthorizeResponse
{
    public function getTransactionReference()
    {
        return $this->request->getTransactionReference();
    }
}
