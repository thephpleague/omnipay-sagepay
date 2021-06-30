<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\SagePay\Traits\ResponseRestFieldsTrait;
use Omnipay\SagePay\ConstantsInterface;

/**
 * Sage Pay Rest Response
 */
class RestResponse extends AbstractResponse implements RedirectResponseInterface, ConstantsInterface
{
    use ResponseRestFieldsTrait;

    /**
     * Gateway Reference
     *
     * Rest API - use just transactionId
     *
     * @return string.
     */
    public function getTransactionReference()
    {
        return $this->getDataItem('transactionId');
    }

    /**
     * The only reason supported for a redirect from a Server transaction
     * will be 3D Secure. PayPal may come into this at some point.
     *
     * @return bool True if a 3DSecure Redirect needs to be performed.
     */
    public function isRedirect()
    {
        return $this->getStatus() === static::SAGEPAY_STATUS_3DAUTH;
    }

    /**
     * @return string URL to 3D Secure endpoint.
     */
    public function getRedirectUrl()
    {
        if ($this->isRedirect()) {
            return $this->getDataItem('acsUrl');
        }
    }

    /**
     * @return string The redirect method.
     */
    public function getRedirectMethod()
    {
        return 'POST';
    }

    /**
     * The usual reason for a redirect is for a 3D Secure check.
     * Note: when PayPal is supported, a different set of data will be returned.
     *
     * @return array Collected 3D Secure POST data.
     */
    public function getRedirectData()
    {
        if ($this->isRedirect()) {
            return array(
                'PaReq' => $this->getDataItem('paReq'),
                'TermUrl' => $this->getRequest()->getReturnUrl(),
                'MD' => $this->getRequest()->getMd(),
            );
        }
    }

    /**
     * The Sage Pay ID to uniquely identify the transaction on their system.
     * Only present if Status is OK or OK REPEATED.
     *
     * @return string
     */
    public function getVPSTxId()
    {
        return $this->getDataItem('VPSTxId');
    }

    /**
     * A secret used to sign the notification request sent direct to your
     * application.
     */
    public function getSecurityKey()
    {
        return $this->getDataItem('SecurityKey');
    }

    /**
     * A secret used to sign the notification request sent direct to your
     * application.
     */
    public function getErrors()
    {
        return $this->getDataItem('errors');
    }

    public function getError()
    {
        if (!empty($this->getErrors())) {
            return array_values($this->getErrors())[0];
        }
        return null;
    }

    /**
     * A secret used to sign the notification request sent direct to your
     * application.
     */
    public function getErrorCode()
    {
        if ($this->getError()) {
            $error = $this->getError();
            return $error['code'] ?? null;
        }
        return $this->getCode();
    }

    /**
     * A secret used to sign the notification request sent direct to your
     * application.
     */
    public function getErrorDescription()
    {
        if ($this->getError()) {
            $error = $this->getError();
            return $error['description'] ?? null;
        }
        return $this->getMessage();
    }
}
