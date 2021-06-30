<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\SagePay\Message\ServerRestCompleteResponse;

/**
 * Sage Pay REST Complete Purchase Request.
 */
class ServerRestCompletePurchaseRequest extends AbstractRestRequest
{
    public function getService()
    {
        return static::SERVICE_REST_3D;
    }

    public function getParentService()
    {
        return static::SERVICE_REST_TRANSACTIONS;
    }

    public function getParentServiceReference()
    {
        return $this->getParameter('transactionId');
    }

    public function getData()
    {

        $data = array(
            'MD' => $this->getMd() ?: $this->httpRequest->request->get('MD'),
            'paRes' => $this->getPaRes() ?: $this->httpRequest->request->get('PaRes'),
        );

        if (empty($data['MD']) || empty($data['paRes'])) {
            throw new InvalidResponseException;
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getMd()
    {
        return $this->getParameter('MD');
    }

    /**
     * Override the MD passed into the current request.
     *
     * @param string $value
     * @return $this
     */
    public function setMd($value)
    {
        return $this->setParameter('MD', $value);
    }

    /**
     * @return string
     */
    public function getPaRes()
    {
        return $this->getParameter('paRes');
    }

    /**
     * Override the PaRes passed into the current request.
     *
     * @param string $value
     * @return $this
     */
    public function setPaRes($value)
    {
        return $this->setParameter('paRes', $value);
    }

    /**
     * @param array $data
     * @return ServerRestCompleteResponse
     */
    protected function createResponse($data)
    {
        return $this->response = new ServerRestCompleteResponse($this, $data);
    }
}
