<?php

namespace Omnipay\SagePay\Message;

use Omnipay\SagePay\Message\ServerRestRepeatResponse;

/**
 * Sage Pay REST Server Repeat Request
 */
class ServerRestRepeatRequest extends AbstractRestRequest
{
    public function getService()
    {
        return static::SERVICE_REST_TRANSACTIONS;
    }
    
    /**
     * @return string the transaction type
     */
    public function getTxType()
    {
        return static::TXTYPE_REPEAT;
    }

    /**
     * Add the optional token details to the base data.
     *
     * @return array
     */
    public function getData()
    {
        $data = $this->getBaseData();

        $data['transactionType'] = $this->getTxType();
        $data['vendorTxCode'] = $this->getTransactionId();
        $data['description'] = $this->getDescription();
        $data['amount'] = (int) $this->getAmount();
        $data['currency'] = $this->getCurrency();
        $data['referenceTransactionId'] = $this->getReferenceTransactionId();

        return $data;
    }

    /**
     * @param array $data
     * @return ServerRestRepeatResponse
     */
    protected function createResponse($data)
    {
        return $this->response = new ServerRestRepeatResponse($this, $data);
    }

    public function getReferenceTransactionId()
    {
        return $this->getParameter('referenceTransactionId');
    }

    public function setReferenceTransactionId($value)
    {
        return $this->setParameter('referenceTransactionId', $value);
    }
}
