<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Helper;

/**
 * Sage Pay Direct Repeat Payment Request
 */
class DirectRepeatPaymentRequest extends AbstractRequest
{
    protected $action = 'REPEAT';

    public function getData()
    {
        $data = $this->getBaseData();

        // Merchant's unique reference to THIS payment
        $data['VendorTxCode'] = $this->getTransactionId();

        $data['Amount'] = $this->getAmount();
        $data['Currency'] = 'GBP';
        $data['Description'] = $this->getDescription();

        // SagePay's unique reference for the PREVIOUS transaction
        $data['RelatedVPSTxId'] = $this->getRelatedVPSTxId();
        $data['RelatedVendorTxCode'] = $this->getRelatedVendorTxCode();
        $data['RelatedSecurityKey'] = $this->getRelatedSecurityKey();
        $data['RelatedTxAuthNo'] = $this->getRelatedTxAuthNo();

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
