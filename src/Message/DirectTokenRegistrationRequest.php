<?php

namespace Omnipay\SagePay\Message;

class DirectTokenRegistrationRequest extends AbstractRequest
{
    protected $action = 'TOKEN';
    protected $cardBrandMap = array(
        'mastercard' => 'mc',
        'diners_club' => 'dc'
    );

    public function getData()
    {
        $this->validate('card');

        $data = $this->getBaseData();

        $data['VendorTxCode'] = $this->getTransactionId();
        $data['Currency'] = $this->getCurrency();
        $data['CardHolder'] = $this->getCard()->getBillingName();
        $data['CardNumber'] = $this->getCard()->getNumber();
        $data['ExpiryDate'] = $this->getCard()->getExpiryDate('my');
        $data['CV2'] = $this->getCard()->getCvv();
        $data['CardType'] = $this->getCardBrand();

        unset($data['AccountType']);

        return $data;
    }

    public function getService()
    {
        return 'directtoken';
    }

    protected function getCardBrand()
    {
        $brand = $this->getCard()->getBrand();

        if (isset($this->cardBrandMap[$brand])) {
            return $this->cardBrandMap[$brand];
        }

        return $brand;
    }
}
