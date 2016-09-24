<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Helper;

/**
 * Sage Pay Direct Repeat Authorize Request
 */
class SharedRepeatAuthorizeRequest extends AbstractRequest
{
    protected $action = 'REPEATDEFERRED';

    public function getData()
    {
        // API version and account details.
        $data = $this->getBaseData();

        // Merchant's unique reference to THIS new authorization or payment
        $data['VendorTxCode'] = $this->getTransactionId();

        // Major currency units.
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
     * @param string|array $jsonEncodedReference JSON-encoded reference to the original transaction
     */
    public function setTransactionReference($jsonEncodedReference)
    {
        if (is_string($jsonEncodedReference)) {
            $unpackedReference = json_decode($jsonEncodedReference, true);
        } elseif (is_array($jsonEncodedReference)) {
            $unpackedReference = $jsonEncodedReference;
        } else {
            throw new InvalidRequestException('transactionReference must be an array or JSON array');
        }

        foreach ($unpackedReference as $parameter => $value) {
            $methodName = 'setRelated'.$parameter;
            if (method_exists($this, $methodName)) {
                $this->$methodName($value);
            }
        }
    }

    /**
     * @deprecated Use setTransactionReference()
     */
    public function setRelatedTransactionReference($jsonEncodedReference)
    {
        return $this->setTransactionReference($jsonEncodedReference);
    }




    /**
     * The original transaction remote gateway ID.
     */
    protected function setRelatedVPSTxId($value)
    {
        return $this->setParameter('relatedVPSTxId', $value);
    }

    protected function getRelatedVPSTxId()
    {
        return $this->getParameter('relatedVPSTxId');
    }

    /**
     * The original transaction local ID (transactionId).
     */
    protected function setRelatedVendorTxCode($value)
    {
        return $this->setParameter('relatedVendorTxCode', $value);
    }

    protected function getRelatedVendorTxCode()
    {
        return $this->getParameter('relatedVendorTxCode');
    }

    /**
     * The original transaction random security key for hashing,
     * never exposed to end users.
     */
    protected function setRelatedSecurityKey($value)
    {
        return $this->setParameter('relatedSecurityKey', $value);
    }

    protected function getRelatedSecurityKey()
    {
        return $this->getParameter('relatedSecurityKey');
    }

    /**
     * The original transaction bank authorisation number.
     */
    protected function setRelatedTxAuthNo($value)
    {
        return $this->setParameter('relatedTxAuthNo', $value);
    }

    protected function getRelatedTxAuthNo()
    {
        return $this->getParameter('relatedTxAuthNo');
    }
}
