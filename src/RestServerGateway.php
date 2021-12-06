<?php

namespace Omnipay\SagePay;

use Omnipay\SagePay\Message\ServerRestVoidRequest;
use Omnipay\SagePay\Message\ServerRestRefundRequest;
use Omnipay\SagePay\Message\ServerRestRepeatRequest;
use Omnipay\SagePay\Message\ServerRestPurchaseRequest;
use Omnipay\SagePay\Message\ServerRestCompletePurchaseRequest;
use Omnipay\SagePay\Message\ServerRestMerchantSessionKeyRequest;
use Omnipay\SagePay\Message\ServerRestRetrieveTransactionRequest;

/**
 * Sage Pay Rest Server Gateway
 */
class RestServerGateway extends ServerGateway
{
    public function getName()
    {
        return 'Sage Pay REST Server';
    }

    public function getUsername()
    {
        return $this->getParameter('username');
    }

    public function setUsername($value)
    {
        return $this->setParameter('username', $value);
    }

    public function getPassword()
    {
        return $this->getParameter('password');
    }

    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }

    /**
     * Create merchant session key (MSK).
     */
    public function createMerchantSessionKey(array $parameters = [])
    {
        return $this->createRequest(ServerRestMerchantSessionKeyRequest::class, $parameters);
    }

    /**
     * Purchase and handling of return from 3D Secure redirection.
     */
    public function purchase(array $parameters = [])
    {
        return $this->createRequest(ServerRestPurchaseRequest::class, $parameters);
    }

    /**
     * Handle purchase notifcation callback.
     */
    public function complete(array $parameters = [])
    {
        return $this->createRequest(ServerRestCompletePurchaseRequest::class, $parameters);
    }

    /**
     * Get transaction information from Sage.
     */
    public function getTransaction(array $parameters = [])
    {
        return $this->fetchTransaction($parameters);
    }

    /**
     * Fetch transaction information from Sage.
     */
    public function fetchTransaction(array $parameters = [])
    {
        return $this->createRequest(ServerRestRetrieveTransactionRequest::class, $parameters);
    }

    /**
     * Refund request.
     */
    public function refund(array $parameters = [])
    {
        return $this->createRequest(ServerRestRefundRequest::class, $parameters);
    }

    /**
     * Repeat request.
     */
    public function repeat(array $parameters = [])
    {
        return $this->createRequest(ServerRestRepeatRequest::class, $parameters);
    }

    /**
     * Void request.
     */
    public function void(array $parameters = [])
    {
        return $this->createRequest(ServerRestVoidRequest::class, $parameters);
    }
}
