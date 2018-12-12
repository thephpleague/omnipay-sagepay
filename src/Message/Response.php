<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\SagePay\Traits\ResponseFieldsTrait;
use Omnipay\SagePay\ConstantsInterface;

/**
 * Sage Pay Response
 */
class Response extends AbstractResponse implements RedirectResponseInterface, ConstantsInterface
{
    use ResponseFieldsTrait;

    /**
     * Gateway Reference
     *
     * Sage Pay requires the original VendorTxCode as well as 3 separate
     * fields from the response object to capture or refund transactions at a later date.
     *
     * Active Merchant solves this dilemma by returning the gateway reference in the following
     * format: VendorTxCode;VPSTxId;TxAuthNo;SecurityKey
     *
     * We have opted to return this reference as JSON, as the keys are much more explicit.
     *
     * @return string|null JSON formatted data.
     */
    public function getTransactionReference()
    {
        $reference = [];

        foreach (['SecurityKey', 'TxAuthNo', 'VPSTxId', 'VendorTxCode'] as $key) {
            $value = $this->getDataItem($key);

            if ($value !== null) {
                $reference[$key] = $value;
            }
        }

        // The reference is null if we have no transaction details.

        if (empty($reference)) {
            return;
        }

        // Remaining transaction details supplied by the merchant site
        // if not already in the response (it will be for Sage Pay Form).

        if (! array_key_exists('VendorTxCode', $reference)) {
            $reference['VendorTxCode'] = $this->getTransactionId();
        }

        ksort($reference);

        return json_encode($reference);
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
            return $this->getDataItem('ACSURL');
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
                'PaReq' => $this->getDataItem('PAReq'),
                'TermUrl' => $this->getRequest()->getReturnUrl(),
                'MD' => $this->getDataItem('MD'),
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
}
