<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Sage Pay Direct Complete Authorize Request.
 */
class DirectCompleteAuthorizeRequest extends AbstractRequest
{
    public function getService()
    {
        return static::SERVICE_DIRECT3D;
    }

    public function getData()
    {
        // Inconsistent letter case is intentional.
        // The issuing bank will return PaRes, but the merchant
        // site must send this result as PARes to Sage Pay.

        $data = array(
            'MD' => $this->getMd() ?: $this->httpRequest->request->get('MD'),
            'PARes' => $this->getPaRes() ?: $this->httpRequest->request->get('PaRes'),
        );

        if (empty($data['MD']) || empty($data['PARes'])) {
            throw new InvalidResponseException;
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getMd()
    {
        return $this->getParameter('md');
    }

    /**
     * Override the MD passed into the current request.
     *
     * @param string $value
     * @return $this
     */
    public function setMd($value)
    {
        return $this->setParameter('md', $value);
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
}
