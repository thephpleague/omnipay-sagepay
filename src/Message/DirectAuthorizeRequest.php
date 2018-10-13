<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Direct Authorize Request
 */

class DirectAuthorizeRequest extends AbstractRequest
{
    /**
     * @var array Some mapping from Omnipay card brand codes to Sage Pay card branc codes.
     */
    protected $cardBrandMap = array(
        'mastercard' => 'MC',
        'diners_club' => 'DC'
    );

    /**
     * @return string the transaction type
     */
    public function getTxType()
    {
        if ($this->getUseAuthenticate()) {
            return static::TXTYPE_AUTHENTICATE;
        } else {
            return static::TXTYPE_DEFERRED;
        }
    }

    public function getService()
    {
        return static::SERVICE_DIRECT_REGISTER;
    }

    /**
     * The required fields concerning what is being authorised and who
     * it is being authorised for.
     *
     * @return array
     */
    protected function getBaseAuthorizeData()
    {
        $this->validate('amount', 'card', 'transactionId');

        // Start with the authorisation and API version details.
        $data = $this->getBaseData();

        $data['Description'] = $this->getDescription();

        // Money formatted as major unit decimal.
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

        // Billing details

        $data = $this->getBillingAddressData($data);

        // Shipping details

        $data = $this->getDeliveryAddressData($data);

        $card = $this->getCard();

        if ($card->getEmail()) {
            $data['CustomerEMail'] = $card->getEmail();
        }

        if ((bool)$this->getUseOldBasketFormat()) {
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
            $data['surchargeXml'] = $surchargeXml;
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
                // If we are using a cardReference, then keep it stored
                // after this transaction for future use.
                // We consider a cardReference as long term, and a token
                // as single-use.

                if ((bool)$this->getCardReference()) {
                    $data['StoreToken'] = static::STORE_TOKEN_YES;
                }
            } elseif ($storeToken !== static::STORE_TOKEN_YES
                && $storeToken !== static::STORE_TOKEN_NO
            ) {
                // A store token to treat as a boolean has been supplied.

                $data['StoreToken'] = (bool)$storeToken
                    ? static::STORE_TOKEN_YES
                    : static::STORE_TOKEN_NO;
            } else {
                // A valid store token to use directly has been supplied.

                $data['StoreToken'] = $storeToken;
            }
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

        $createCard = $this->getCreateToken() ?: $this->getCreateCard();

        if ($createCard !== null) {
            $data['CreateToken'] = $createCard ? static::CREATE_TOKEN_YES : static::CREATE_TOKEN_NO;
        }

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
