<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Helper;

/**
 * Sage Pay Direct Repeat Authorize Request
 */
class DirectRepeatAuthorizeRequest extends AbstractRequest
{
    protected $action = 'REPEATDEFERRED';

    public function getData()
    {
        $data = $this->getBaseData();

        // Merchant's unique reference to THIS payment
        $data['VendorTxCode'] = $this->getTransactionId();

        $data['Amount'] = $this->getAmount();
        $data['Currency'] = $this->getCurrency();
        $data['Description'] = $this->getDescription();

        // SagePay's unique reference for the PREVIOUS transaction
        $data['RelatedVPSTxId'] = $this->getRelatedVPSTxId();
        $data['RelatedVendorTxCode'] = $this->getRelatedVendorTxCode();
        $data['RelatedSecurityKey'] = $this->getRelatedSecurityKey();
        $data['RelatedTxAuthNo'] = $this->getRelatedTxAuthNo();

        // Some details in the card can be changed for the repeat purchase.
        $card = $this->getCard();

        // If a card is provided, then assume all billing details are being updated.
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

        // The documentation lists only BasketXML as supported for repeat transactions, and not Basklet.
        // CHECKME: is this a documentation error?

        $basketXML = $this->getItemData();
        if (! empty($basketXML)) {
            $data['BasketXML'] = $basketXML;
        }

        return $data;
    }

    public function getDescription()
    {
        return $this->getParameter('description');
    }

    public function getService()
    {
        return 'repeat';
    }

    public function setDescription($value)
    {
        return $this->setParameter('description', $value);
    }

    /**
     * This is a direct map to Omnipay\SagePay\Message\Response::getTransactionReference()
     *
     * @param string $jsonEncodedReference JSON-encoded reference to the original transaction
     */
    public function setRelatedTransactionReference($jsonEncodedReference)
    {
        $unpackedReference = json_decode($jsonEncodedReference, true);
        foreach ($unpackedReference as $parameter => $value) {
            $methodName = 'setRelated'.$parameter;
            if (method_exists($this, $methodName)) {
                $this->$methodName($value);
            }
        }
    }

    protected function getRelatedVPSTxId()
    {
        return $this->getParameter('relatedVPSTxId');
    }

    protected function getRelatedVendorTxCode()
    {
        return $this->getParameter('relatedVendorTxCode');
    }

    protected function getRelatedSecurityKey()
    {
        return $this->getParameter('relatedSecurityKey');
    }

    protected function getRelatedTxAuthNo()
    {
        return $this->getParameter('relatedTxAuthNo');
    }

    protected function setRelatedSecurityKey($value)
    {
        return $this->setParameter('relatedSecurityKey', $value);
    }

    protected function setRelatedTxAuthNo($value)
    {
        return $this->setParameter('relatedTxAuthNo', $value);
    }

    protected function setRelatedVendorTxCode($value)
    {
        return $this->setParameter('relatedVendorTxCode', $value);
    }

    protected function setRelatedVPSTxId($value)
    {
        return $this->setParameter('relatedVPSTxId', $value);
    }
}
