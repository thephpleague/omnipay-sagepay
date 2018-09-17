<?php

namespace Omnipay\SagePay\Message;

/**
 * Register a card with the gateway in exchange for a token.
 */

class DirectTokenRegistrationRequest extends AbstractRequest
{
    protected $cardBrandMap = array(
        'mastercard' => 'mc',
        'diners_club' => 'dc'
    );

    public function getService()
    {
        return static::SERVICE_TOKEN;
    }

    public function getTxType()
    {
        return static::TXTYPE_TOKEN;
    }

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

        // The account type only comes into play when a transation is requested.
        unset($data['AccountType']);

        return $data;
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
