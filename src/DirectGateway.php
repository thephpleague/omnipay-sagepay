<?php

namespace Omnipay\SagePay;

use Omnipay\Common\AbstractGateway;
use Omnipay\SagePay\Message\CaptureRequest;
use Omnipay\SagePay\Message\DirectAuthorizeRequest;
use Omnipay\SagePay\Message\DirectPurchaseRequest;
use Omnipay\SagePay\Message\RefundRequest;

/**
 * Sage Pay Direct Gateway
 */
class DirectGateway extends AbstractGateway
{
    // Gateway identification.

    public function getName()
    {
        return 'Sage Pay Direct';
    }

    public function getDefaultParameters()
    {
        return array(
            'vendor' => '',
            'testMode' => false,
        );
    }

    // Vendor identification.

    public function getVendor()
    {
        return $this->getParameter('vendor');
    }

    public function setVendor($value)
    {
        return $this->setParameter('vendor', $value);
    }

    // Access to the HTTP client for debugging.

    public function getHttpClient()
    {
        return $this->httpClient;
    }

    // Available services.

    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\DirectAuthorizeRequest', $parameters);
    }

    public function completeAuthorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\DirectCompleteAuthorizeRequest', $parameters);
    }

    public function capture(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\CaptureRequest', $parameters);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\DirectPurchaseRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->completeAuthorize($parameters);
    }

    public function refund(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\RefundRequest', $parameters);
    }
}
