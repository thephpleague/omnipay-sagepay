<?php

namespace Omnipay\SagePay\Message;

class ServerTokenRegistrationRequest extends AbstractRequest
{
    protected $action = 'TOKEN';

    /**
     * @return mixed
     */
    public function getData()
    {
        $data = $this->getBaseData();

        $data['Currency'] = $this->getCurrency();
        $data['NotificationURL'] = $this->getNotifyUrl() ?: $this->getReturnUrl();
        $data['VendorTxCode'] = $this->getTransactionId();

        unset($data['AccountType']);

        return $data;
    }

    /**
     * @param mixed $data
     * @return ServerTokenRegistrationResponse
     */
    public function createResponse($data)
    {
        return $this->response = new ServerTokenRegistrationResponse($this, $data);
    }
}
