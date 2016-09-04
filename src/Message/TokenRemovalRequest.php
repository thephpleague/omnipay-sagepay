<?php

namespace Omnipay\SagePay\Message;

class TokenRemovalRequest extends AbstractRequest
{
    protected $action = 'REMOVETOKEN';

    /**
     * @return mixed
     */
    public function getData()
    {
        $data = $this->getBaseData();
        $data['Token'] = $this->getToken();

        unset($data['AccountType']);

        return $data;
    }

    public function getService()
    {
        return 'removetoken';
    }
}
