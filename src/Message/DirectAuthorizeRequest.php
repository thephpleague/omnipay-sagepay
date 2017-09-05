<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Direct Authorize Request
 */
class DirectAuthorizeRequest extends AbstractRequest
{
    protected $action = 'DEFERRED';
    protected $cardBrandMap = array(
        'mastercard' => 'mc',
        'diners_club' => 'dc'
    );

    protected function getBaseAuthorizeData()
    {
        $this->validate('amount', 'card', 'transactionId');
        $card = $this->getCard();

        $data = $this->getBaseData();
        $data['Description'] = $this->getDescription();
        $data['Amount'] = $this->getAmount();
        $data['Currency'] = $this->getCurrency();
        $data['VendorData'] = $this->getVendorData();
        $data['VendorTxCode'] = $this->getTransactionId();
        $data['ClientIPAddress'] = $this->getClientIp();
        $data['ApplyAVSCV2'] = $this->getApplyAVSCV2() ?: 0;
        $data['Apply3DSecure'] = $this->getApply3DSecure() ?: 0;

        $data['CreateToken'] = $this->getCreateToken();

        // Creating a token should not be permissible at
        // the same time as using a token.
        if (! $data['CreateToken'] && $this->getToken()) {
            // If a token has been supplied, and we are NOT asking to generate
            // a new token here, then use this token and optionally store it
            // again for further use.
            $data['Token'] = $this->getToken();
            $data['StoreToken'] = $this->getStoreToken();
        }

        if ($this->getReferrerId()) {
            $data['ReferrerID'] = $this->getReferrerId();
        }

        // billing details
        $data['BillingFirstnames'] = $card->getBillingFirstName();
        $data['BillingSurname'] = $card->getBillingLastName();
        $data['BillingAddress1'] = $card->getBillingAddress1();
        $data['BillingAddress2'] = $card->getBillingAddress2();
        $data['BillingCity'] = $card->getBillingCity();
        $data['BillingPostCode'] = $card->getBillingPostcode();
        $data['BillingState'] = $card->getBillingCountry() === 'US' ? $card->getBillingState() : '';
        $data['BillingCountry'] = $card->getBillingCountry();
        $data['BillingPhone'] = $card->getBillingPhone();

        // shipping details
        $data['DeliveryFirstnames'] = $card->getShippingFirstName();
        $data['DeliverySurname'] = $card->getShippingLastName();
        $data['DeliveryAddress1'] = $card->getShippingAddress1();
        $data['DeliveryAddress2'] = $card->getShippingAddress2();
        $data['DeliveryCity'] = $card->getShippingCity();
        $data['DeliveryPostCode'] = $card->getShippingPostcode();
        $data['DeliveryState'] = $card->getShippingCountry() === 'US' ? $card->getShippingState() : '';
        $data['DeliveryCountry'] = $card->getShippingCountry();
        $data['DeliveryPhone'] = $card->getShippingPhone();
        $data['CustomerEMail'] = $card->getEmail();

        if ($this->getUseOldBasketFormat()) {
            $basket = $this->getItemDataNonXML();
            if (!empty($basket)) {
                $data['Basket'] = $basket;
            }
        } else {
            $basketXML = $this->getItemData();
            if (!empty($basketXML)) {
                $data['BasketXML'] = $basketXML;
            }
        }

        $surchargeXml = $this->getSurchargeXml();

        if ($surchargeXml) {
            $data['surchargeXml'] = $this->getSurchargeXml();
        }

        return $data;
    }

    /**
     * SagePay throws an error if passed an IPv6 address.
     * Filter out addresses that are not IPv4 format.
     */
    public function getClientIp()
    {
        $ip = parent::getClientIp();

        // OmniPay core could do with a helper for this.
        if (! preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $ip)) {
            $ip = null;
        }

        return $ip;
    }

    /*
     * Set cardholder name directly, overriding the billing name and surname of the card.
     */
    public function setCardholderName($value)
    {
        return $this->setParameter('cardholderName', $value);
    }

    public function getCardholderName()
    {
        return $this->getParameter('cardholderName');
    }

    public function getData()
    {
        $data = $this->getBaseAuthorizeData();
        $this->getCard()->validate();

        if ($this->getCardholderName()) {
            $data['CardHolder'] = $this->getCardholderName();
        } else {
            $data['CardHolder'] = $this->getCard()->getName();
        }

        // Card number should not be provided if token is being provided instead
        if (!$this->getToken()) {
            $data['CardNumber'] = $this->getCard()->getNumber();
        }

        $data['CV2'] = $this->getCard()->getCvv();
        $data['ExpiryDate'] = $this->getCard()->getExpiryDate('my');
        $data['CardType'] = $this->getCardBrand();

        if ($this->getCard()->getStartMonth() and $this->getCard()->getStartYear()) {
            $data['StartDate'] = $this->getCard()->getStartDate('my');
        }

        if ($this->getCard()->getIssueNumber()) {
            $data['IssueNumber'] = $this->getCard()->getIssueNumber();
        }

        return $data;
    }

    public function getService()
    {
        return 'vspdirect-register';
    }

    protected function getCardBrand()
    {
        $brand = $this->getCard()->getBrand();

        if (isset($this->cardBrandMap[$brand])) {
            return $this->cardBrandMap[$brand];
        }

        return $brand;
    }

    /**
     * Set the raw surcharge XML field.
     *
     * @param string $surchargeXml The XML data formatted as per Sage Pay documentation.
     * @return $this
     */
    public function setSurchargeXml($surchargeXml)
    {
        return $this->setParameter('surchargeXml', $surchargeXml);
    }

    /**
     * @return string The XML surchange data as set.
     */
    public function getSurchargeXml()
    {
        return $this->getParameter('surchargeXml');
    }
}
