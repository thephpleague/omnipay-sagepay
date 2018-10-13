<?php

namespace Omnipay\SagePay\Traits;

//use Omnipay\Common\Exception\InvalidResponseException;
//use Omnipay\Common\Message\NotificationInterface;
//use Omnipay\SagePay\Message\Response;

/**
 * Parameters that can be set at the gateway class, and so
 * must also be available at the request message class.
 */

trait GatewayParamsTrait
{
    /**
     * @return string The vendor name identified the account.
     */
    public function getVendor()
    {
        return $this->getParameter('vendor');
    }

    /**
     * @param string $value The vendor name, as supplied in lower case.
     * @return $this Provides a fluent interface.
     */
    public function setVendor($value)
    {
        return $this->setParameter('vendor', $value);
    }

    /**
     * @return string The referrer ID.
     */
    public function getReferrerId()
    {
        return $this->getParameter('referrerId');
    }

    /**
     * @param string $value The referrer ID for PAYMENT, DEFERRED and AUTHENTICATE transactions.
     * @return $this Provides a fluent interface.
     */
    public function setReferrerId($value)
    {
        return $this->setParameter('referrerId', $value);
    }

    /**
     * By default, the XML basket format will be used. This flag can be used to
     * switch back to the older terminated-string format basket. Each basket
     * format supports a different range of features, both in the basket itself
     * and in the data collected and processed in the gateway backend.
     *
     * @param mixed $value Casts to true to switch the old format basket.
     * @return $this
     */
    public function setUseOldBasketFormat($value)
    {
        return $this->setParameter('useOldBasketFormat', $value);
    }

    /**
     * Returns the current basket format by indicating whether the older
     * terminated-string format is being used.
     *
     * @return mixed true for old format basket; false for newer XML format basket.
     */
    public function getUseOldBasketFormat()
    {
        return $this->getParameter('useOldBasketFormat');
    }

    /*
     * Used in the notification handler to exist immediately once
     * the response message is echoed. The older default was true,
     * and this option allows it to be turned back on for legacy
     * merchant sites.
     *
     * @param mixed true if the notify reponse exits the application.
     * @return $this
     */
    public function setExitOnResponse($value)
    {
        return $this->setParameter('exitOnResponse', $value);
    }

    /**
     * @return mixed true if the notify reponse exits the application.
     */
    public function getExitOnResponse()
    {
        return $this->getParameter('exitOnResponse');
    }

    /**
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    /**
     * Set language to instruct sagepay, on which language will be seen
     * on payment pages.
     *
     * @param string $value ISO 639 alpha-2 character language code.
     * @return $this
     */
    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }

    /**
     * @return int|null One of APPLY_3DSECURE_*
     */
    public function getApply3DSecure()
    {
        return $this->getParameter('apply3DSecure');
    }

    /**
     * Whether or not to apply 3D secure authentication.
     *
     * This is ignored for PAYPAL, EUROPEAN PAYMENT transactions.
     * Values defined in APPLY_3DSECURE_* constant.
     *
     * For values see constants APPLY_3DSECURE_*
     *
     * @param int $value 0-3
     * @return $this
     */
    public function setApply3DSecure($value)
    {
        return $this->setParameter('apply3DSecure', $value);
    }

    /**
     * @return int|null One of APPLY_AVSCV2_*
     */
    public function getApplyAVSCV2()
    {
        return $this->getParameter('applyAVSCV2');
    }

    /**
     * Set the apply AVSCV2 checks.
     * Values defined in APPLY_AVSCV2_* constants.
     *
     * @param int $value 0-3
     */
    public function setApplyAVSCV2($value)
    {
        return $this->setParameter('applyAVSCV2', $value);
    }

    /**
     * Set this parameter to use AUTHENTICATE/AUTHORISE instead
     * of DEFERRED/RELEASE for the authorize() function.
     *
     * @param mixed $value Casts to true to switch to the authenticate model.
     * @return $this
     */
    public function setUseAuthenticate($value)
    {
        return $this->setParameter('useAuthenticate', $value);
    }

    /**
     * @return mixed true for old format basket; false for newer XML format basket.
     */
    public function getUseAuthenticate()
    {
        return $this->getParameter('useAuthenticate');
    }

    /**
     * @return string One of static::ACCOUNT_TYPE_*
     */
    public function getAccountType()
    {
        return $this->getParameter('accountType');
    }

    /**
     * Set account type.
     * Neither 'M' nor 'C' offer the 3D-Secure checks that the "E" customer
     * experience offers. See constants ACCOUNT_TYPE_*
     *
     * This is ignored for all PAYPAL transactions.
     *
     * @param string $value E: Use the e-commerce merchant account. (default)
     *                      M: Use the mail/telephone order account. (if present)
     *                      C: Use the continuous authority merchant account. (if present)
     * @return $this
     */
    public function setAccountType($value)
    {
        return $this->setParameter('accountType', $value);
    }

    /**
     * @return string|null Encryption key for Sage Pay Form
     */
    public function getEncryptionKey()
    {
        return $this->getParameter('encryptionKey');
    }

    /**
     * @param string $value Encryption key for Sage Pay Form; aka form password
     * @return $this
     */
    public function setEncryptionKey($value)
    {
        return $this->setParameter('encryptionKey', $value);
    }

    /**
     * @return mixed
     */
    public function getBillingForShipping()
    {
        return $this->getParameter('billingForShipping');
    }

    /**
     * Set to force the billing address to be used as the shipping address.
     *
     * @param mixed $value Will be evaluated as boolean.
     * @return $this
     */
    public function setBillingForShipping($value)
    {
        return $this->setParameter('billingForShipping', $value);
    }

    /**
     * @return mixed
     */
    public function getDisableUtf8Decode()
    {
        return $this->getParameter('disableUtf8Decode');
    }

    /**
     * The Form API will convert all input data from an assumed UTF-8
     * encoding to ISO8859-1 by default, unless disabled here.
     *
     * @param mixed $value Will be evaluated as boolean.
     * @return $this
     */
    public function setDisableUtf8Decode($value)
    {
        return $this->setParameter('disableUtf8Decode', $value);
    }
}
