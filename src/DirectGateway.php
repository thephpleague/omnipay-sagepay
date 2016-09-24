<?php

namespace Omnipay\SagePay;

use Omnipay\Common\AbstractGateway;

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
            'referrerId' => '',
        );
    }

    /**
     * Vendor identification.
     */
    public function getVendor()
    {
        return $this->getParameter('vendor');
    }

    public function setVendor($value)
    {
        return $this->setParameter('vendor', $value);
    }

    public function getReferrerId()
    {
        return $this->getParameter('referrerId');
    }

    public function setReferrerId($value)
    {
        return $this->setParameter('referrerId', $value);
    }

    /**
     * Basket type control.
     */
    public function setUseOldBasketFormat($value)
    {
        $value = (bool)$value;

        return $this->setParameter('useOldBasketFormat', $value);
    }

    public function getUseOldBasketFormat()
    {
        return $this->getParameter('useOldBasketFormat');
    }

    // Access to the HTTP client for debugging.
    // NOTE: this is likely to be removed or replaced with something
    // more appropriate.

    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Direct methods.
     */

    /**
     * Authorize and handling of return from 3D Secure or PayPal redirection.
     */
    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\DirectAuthorizeRequest', $parameters);
    }

    public function completeAuthorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\DirectCompleteAuthorizeRequest', $parameters);
    }

    /**
     * Purchase and handling of return from 3D Secure or PayPal redirection.
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\DirectPurchaseRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->completeAuthorize($parameters);
    }

    /**
     * Shared methods (identical for Direct and Server).
     */

    /**
     * Capture an authorization.
     */
    public function capture(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\SharedCaptureRequest', $parameters);
    }

    /**
     * Void a paid transaction.
     */
    public function void(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\SharedVoidRequest', $parameters);
    }

    /**
     * Abort an authorization.
     */
    public function abort(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\SharedAbortRequest', $parameters);
    }

    /**
     * Void a completed (captured) transation.
     */
    public function refund(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\SharedRefundRequest', $parameters);
    }

    /**
     * Create a new authorization against a previous payment.
     */
    public function repeatAuthorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\SharedRepeatAuthorizeRequest', $parameters);
    }

    /**
     * Create a new purchase against a previous payment.
     */
    public function repeatPurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\SharedRepeatPurchaseRequest', $parameters);
    }

    /**
     * Accept card details from a user and return a token, without any
     * authorization against that card.
     * i.e. standalone token creation.
     * Alias fof registerToken()
     */
    public function createCard(array $parameters = array())
    {
        return $this->registerToken($parameters);
    }

    /**
     * Accept card details from a user and return a token, without any
     * authorization against that card.
     * i.e. standalone token creation.
     */
    public function registerToken(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\DirectTokenRegistrationRequest', $parameters);
    }

    /**
     * Remove a card token from the account.
     * Alias for removeToken()
     */
    public function deleteCard(array $parameters = array())
    {
        return $this->removeToken($parameters);
    }

    /**
     * Remove a card token from the account.
     */
    public function removeToken(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\SharedTokenRemovalRequest', $parameters);
    }

    /**
     * @deprecated use repeatAuthorize() or repeatPurchase()
     */
    public function repeatPayment(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SagePay\Message\DirectRepeatPaymentRequest', $parameters);
    }
}
