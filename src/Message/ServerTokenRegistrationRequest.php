<?php

namespace Omnipay\SagePay\Message;

class ServerTokenRegistrationRequest extends AbstractRequest
{
    public function getTxType()
    {
        return static::TXTYPE_TOKEN;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        $data = $this->getBaseData();

        $data['Currency'] = $this->getCurrency();
        $data['NotificationURL'] = $this->getNotifyUrl() ?: $this->getReturnUrl();
        $data['VendorTxCode'] = $this->getTransactionId();

        $profile = $this->getProfile();

        if ($profile === static::PROFILE_NORMAL || $profile === static::PROFILE_LOW) {
            $data['Profile'] = $this->getProfile();
        }

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
