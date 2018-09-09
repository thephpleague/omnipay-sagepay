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
     * TODO: support extracting language from locale.
     *
     * @param string $value ISO 639 alpha-2 character language code.
     * @return $this
     */
    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }
}
