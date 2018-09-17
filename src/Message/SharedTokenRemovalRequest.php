<?php

namespace Omnipay\SagePay\Message;

class SharedTokenRemovalRequest extends AbstractRequest
{
    public function getTxType()
    {
        return static::TXTYPE_REMOVETOKEN;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        $data = $this->getBaseData();
        $data['Token'] = $this->getCardReference() ?: $this->getToken();

        unset($data['AccountType']);

        return $data;
    }
}
