<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Helper;

/**
 * Sage Pay Direct Repeat Authorize Request
 */
class SharedRepeatAuthorizeRequest extends AbstractRequest
{
    public function getService()
    {
        return static::SERVICE_REPEAT;
    }

    public function getTxType()
    {
        return static::TXTYPE_REPEATDEFERRED;
    }

    /**
     * @return array The message body data.
     */
    public function getData()
    {
        $this->validate(
            'relatedTransactionId',
            'vpsTxId',
            'securityKey',
            'txAuthNo',
            'currency',
            'description'
        );

        // API version and account details.

        $data = $this->getBaseData();

        // Merchant's unique reference to THIS new authorization or payment

        $data['VendorTxCode'] = $this->getTransactionId();

        // Sent to the gateway as major currency units.

        $data['Amount'] = $this->getAmount();
        $data['Currency'] = $this->getCurrency();

        $data['Description'] = $this->getDescription();

        // SagePay's unique reference for the PREVIOUS transaction

        $data['RelatedVendorTxCode'] = $this->getRelatedTransactionId();
        $data['RelatedVPSTxId'] = $this->getVpsTxId();
        $data['RelatedSecurityKey'] = $this->getSecurityKey();
        $data['RelatedTxAuthNo'] = $this->getTxAuthNo();

        // Some details in the card can be changed for the repeat purchase.

        $card = $this->getCard();

        // If a card is provided, then assume all billing details are being updated.
        // TODO: move this construct to a separate method, as it is used several times.

        if ($card) {
            $data['BillingFirstnames'] = $card->getBillingFirstName();
            $data['BillingSurname'] = $card->getBillingLastName();
            $data['BillingAddress1'] = $card->getBillingAddress1();
            $data['BillingAddress2'] = $card->getBillingAddress2();
            $data['BillingCity'] = $card->getBillingCity();
            $data['BillingPostCode'] = $card->getBillingPostcode();
            $data['BillingState'] = $card->getBillingCountry() === 'US' ? $card->getBillingState() : '';
            $data['BillingCountry'] = $card->getBillingCountry();
            $data['BillingPhone'] = $card->getBillingPhone();

            // If the customer is present, then the CV2 can be supplied again for extra security.

            $cvv = $card->getCvv();

            if (isset($cvv) && $cvv != '') {
                $data['CV2'] = $cvv;
            }
        }

        // The documentation lists only BasketXML as supported for repeat transactions,
        // and not the older CSV Basket.
        // CHECKME: is this a documentation error?

        $basketXML = $this->getItemData();
        if (! empty($basketXML)) {
            $data['BasketXML'] = $basketXML;
        }

        return $data;
    }

    // Everything below here is deprecated.
    // Set the same parameters as used for void and capture.

    /**
     * The original transaction remote gateway ID.
     * @deprec use setVpsTxId() or setRelatedTransactionReference() instead
     */
    protected function setRelatedVPSTxId($value)
    {
        return $this->setVpsTxId($value);
    }

    /**
     * @deprec use getVpsTxId() instead
     */
    protected function getRelatedVPSTxId()
    {
        return $this->getVpsTxId();
    }

    /**
     * The original transaction local ID (transactionId).
     * @deprec use setRelatedTransactionId() or setRelatedTransactionReference() instead
     */
    protected function setRelatedVendorTxCode($value)
    {
        return $this->setRelatedTransactionId($value);
    }

    /**
     * @deprec use getRelatedTransactionId() instead
     */
    protected function getRelatedVendorTxCode()
    {
        return $this->getRelatedTransactionId();
    }

    /**
     * The original transaction random security key for hashing,
     * never exposed to end users.
     * @deprec use setSecurityKey() or setRelatedTransactionReference() instead
     */
    protected function setRelatedSecurityKey($value)
    {
        return $this->setSecurityKey($value);
    }

    /**
     * @deprec use getSecurityKey() instead
     */
    protected function getRelatedSecurityKey()
    {
        return $this->getSecurityKey();
    }

    /**
     * The original transaction bank authorisation number.
     * @deprec use setTxAuthNo() or setRelatedTransactionReference() instead
     */
    protected function setRelatedTxAuthNo($value)
    {
        return $this->setTxAuthNo($value);
    }

    /**
     * @deprec use getTxAuthNo() instead
     */
    protected function getRelatedTxAuthNo()
    {
        return $this->getTxAuthNo();
    }
}
