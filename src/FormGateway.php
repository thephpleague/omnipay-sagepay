<?php

namespace Omnipay\SagePay;

use Omnipay\SagePay\Message\Form\AuthorizeRequest;
use Omnipay\SagePay\Message\Form\CompleteAuthorizeRequest;
use Omnipay\SagePay\Message\Form\CompletePurchaseRequest;
use Omnipay\SagePay\Message\Form\PurchaseRequest;

/**
 * Sage Pay Server Gateway
 */
class FormGateway extends AbstractGateway
{
    public function getName()
    {
        return 'Sage Pay Form';
    }

    /**
     * Authorize a payment.
     */
    public function authorize(array $parameters = array())
    {
        return $this->createRequest(AuthorizeRequest::class, $parameters);
    }

    /**
     * Authorize and capture a payment.
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }

    /**
     *
     */
    public function completeAuthorize(array $parameters = array())
    {
        return $this->createRequest(CompleteAuthorizeRequest::class, $parameters);
    }

    /**
     *
     */
    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest(CompletePurchaseRequest::class, $parameters);
    }
}
