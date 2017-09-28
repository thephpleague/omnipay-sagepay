<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Direct Authorize Request
 */

class DirectAuthorizeRequest extends AbstractRequest
{
    protected $action = 'DEFERRED';
    protected $service = 'vspdirect-register';

    /**
     * @var array Some mapping from Omnipay card brand codes to Sage Pay card branc codes.
     */
    protected $cardBrandMap = array(
        'mastercard' => 'MC',
        'diners_club' => 'DC'
    );

    /**
     * The required fields concerning what is being authorised and who
     * it is being authorised for.
     *
     * @return array
     */
    protected function getBaseAuthorizeData()
    {
        $this->validate('amount', 'card', 'transactionId');
        $card = $this->getCard();

        // Start with the authorisation and API version details.
        $data = $this->getBaseData();

        $data['Description'] = $this->getDescription();
        $data['Amount'] = $this->getAmount();
        $data['Currency'] = $this->getCurrency();

        $data['VendorData'] = $this->getVendorData();
        $data['VendorTxCode'] = $this->getTransactionId();
        $data['ClientIPAddress'] = $this->getClientIp();

        $data['ApplyAVSCV2'] = $this->getApplyAVSCV2() ?: static::APPLY_AVSCV2_DEFAULT;
        $data['Apply3DSecure'] = $this->getApply3DSecure() ?: static::APPLY_3DSECURE_APPLY;

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
        $data['BillingState'] = ($card->getBillingCountry() === 'US' ? $card->getBillingState() : '');
        $data['BillingCountry'] = $card->getBillingCountry();
        $data['BillingPhone'] = $card->getBillingPhone();

        // shipping details
        $data['DeliveryFirstnames'] = $card->getShippingFirstName();
        $data['DeliverySurname'] = $card->getShippingLastName();
        $data['DeliveryAddress1'] = $card->getShippingAddress1();
        $data['DeliveryAddress2'] = $card->getShippingAddress2();
        $data['DeliveryCity'] = $card->getShippingCity();
        $data['DeliveryPostCode'] = $card->getShippingPostcode();
        $data['DeliveryState'] = ($card->getShippingCountry() === 'US' ? $card->getShippingState() : '');
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
     *
     * @return string|null The IPv4 IP addess string or null if not available in this format.
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

    /**
     * If a token or cardReference is being used, then include the details
     * of the token in the data.
     *
     * @param array $data The data collected so far (to be added to).
     * @return array
     */
    public function getTokenData($data = array())
    {
        // Are there token details to add?
        if ($this->getToken() || $this->getCardReference()) {
            // A card token or reference has been provided.
            $data['Token'] = $this->getToken() ?: $this->getCardReference();

            // If we don't have a StoreToken override, then set it according to
            // whether we are dealing with a token or a cardReference.
            // Overriding the default token storage flag is for legacy support.

            $storeToken = $this->getStoreToken();

            if ($storeToken === null) {
                // If we are using the token as a cardReference, then keep it stored
                // after this transaction for future use.

                $storeToken = $this->getCardReference()
                    ? static::STORE_TOKEN_YES
                    : static::STORE_TOKEN_NO;
            }

            $data['StoreToken'] = $storeToken;
        }

        return $data;
    }

    /**
     * If a credit card is being used, then include the details
     * of the card in the data.
     *
     * @param array $data The data collected so far (to be added to).
     * @return array
     */
    public function getCardData($data = array())
    {
        // Validate the card details (number, date, cardholder name).
        $this->getCard()->validate();

        if ($this->getCardholderName()) {
            $data['CardHolder'] = $this->getCardholderName();
        } else {
            $data['CardHolder'] = $this->getCard()->getName();
        }

        // Card number should not be provided if token is being provided instead
        if (! $this->getToken()) {
            $data['CardNumber'] = $this->getCard()->getNumber();
        }

        $data['ExpiryDate'] = $this->getCard()->getExpiryDate('my');
        $data['CardType'] = $this->getCardBrand();

        if ($this->getCard()->getStartMonth() and $this->getCard()->getStartYear()) {
            $data['StartDate'] = $this->getCard()->getStartDate('my');
        }

        if ($this->getCard()->getIssueNumber()) {
            $data['IssueNumber'] = $this->getCard()->getIssueNumber();
        }

        // If we want the card details to be saved on the gateway as a
        // token or card reference, then request for that to be done.
        $data['CreateToken'] = $this->getCreateToken();

        if ($this->getCard()->getCvv() !== null) {
            $data['CV2'] = $this->getCard()->getCvv();
        }

        return $data;
    }

    /**
     * Add the credit card or token details to the data.
     */
    public function getData()
    {
        $data = $this->getBaseAuthorizeData();

        if ($this->getToken() || $this->getCardReference()) {
            // If using a token, then set that data.
            $data = $this->getTokenData($data);
        } else {
            // Otherwise, a credit card has to have been provided.
            $data = $this->getCardData($data);
        }

        // A CVV may be supplied whether using a token or credit card details.
        // On *first* use of a token for which a CVV was provided, that CVV will
        // be used when making a transaction. The CVV will then be deleted by the
        // gateway. For each *resuse* of a cardReference, a new CVV must be provided,
        // if the security rules require it.

        if ($this->getCard()->getCvv() !== null) {
            $data['CV2'] = $this->getCard()->getCvv();
        }

        return $data;
    }

    /**
     * @return string Get the card brand in a format expected by Sage Pay.
     */
    protected function getCardBrand()
    {
        $brand = $this->getCard()->getBrand();

        // Some Omnipay-derived card brands will need mapping to new names.

        if (isset($this->cardBrandMap[$brand])) {
            return $this->cardBrandMap[$brand];
        }

        return strtoupper($brand);
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
