<?php

namespace Omnipay\SagePay\Traits;

trait GatewayParamsTrait {

    public function setThreeDSNotificationURL($value)
    {
        return $this->setParameter('ThreeDSNotificationURL', $value);
    }

    public function getThreeDSNotificationURL()
    {
        return $this->getParameter('ThreeDSNotificationURL');
    }

    public function setBrowserJavascriptEnabled($value)
    {
        return $this->setParameter('BrowserJavascriptEnabled', $value);
    }

    public function getBrowserJavascriptEnabled()
    {
        return $this->getParameter('BrowserJavascriptEnabled');
    }

    public function setBrowserLanguage($value)
    {
        return $this->setParameter('BrowserLanguage', $value);
    }

    public function getBrowserLanguage()
    {
        return $this->getParameter('BrowserLanguage');
    }

    public function setChallengeWindowSize($value)
    {
        return $this->setParameter('ChallengeWindowSize', $value);
    }

    public function getChallengeWindowSize()
    {
        return $this->getParameter('ChallengeWindowSize');
    }

    public function setBrowserJavaEnabled($value)
    {
        return $this->setParameter('BrowserJavaEnabled', $value);
    }

    public function getBrowserJavaEnabled()
    {
        return $this->getParameter('BrowserJavaEnabled');
    }


    public function setBrowserColorDepth($value)
    {
        return $this->setParameter('BrowserColorDepth', $value);
    }

    public function getBrowserColorDepth()
    {
        return $this->getParameter('BrowserColorDepth');
    }

    public function setBrowserScreenHeight($value)
    {
        return $this->setParameter('BrowserScreenHeight', $value);
    }

    public function getBrowserScreenHeight()
    {
        return $this->getParameter('BrowserScreenHeight');
    }

    public function setBrowserScreenWidth($value)
    {
        return $this->setParameter('BrowserScreenWidth', $value);
    }

    public function getBrowserScreenWidth()
    {
        return $this->getParameter('BrowserScreenWidth');
    }

    public function setBrowserTZ($value)
    {
        return $this->setParameter('BrowserTZ', $value);
    }

    public function getBrowserTZ()
    {
        return $this->getParameter('BrowserTZ');
    }

    public function setInitiatedType($value)
    {
        return $this->setParameter('InitiatedType', $value);
    }

    public function getInitiatedType()
    {
        return $this->getParameter('InitiatedType');
    }

    public function setCOFUsage($value)
    {
        return $this->setParameter('COFUsage', $value);
    }

    public function getCOFUsage()
    {
        return $this->getParameter('COFUsage');
    }

    public function setMITType($value)
    {
        return $this->setParameter('MITType', $value);
    }

    public function getMITType()
    {
        return $this->getParameter('MITType');
    }

    public function setSchemeTraceID($value)
    {
        return $this->setParameter('SchemeTraceID', $value);
    }

    public function getSchemeTraceID()
    {
        return $this->getParameter('SchemeTraceID');
    }

    public function setRecurringExpiry($value)
    {
        return $this->setParameter('RecurringExpiry', $value);
    }

    public function getRecurringExpiry()
    {
        return $this->getParameter('RecurringExpiry');
    }

    public function setRecurringFrequency($value)
    {
        return $this->setParameter('RecurringFrequency', $value);
    }

    public function getRecurringFrequency()
    {
        return $this->getParameter('RecurringFrequency');
    }

    public function setACSTransID($value)
    {
        return $this->setParameter('ACSTransID', $value);
    }

    public function getACSTransID()
    {
        return $this->getParameter('ACSTransID');
    }

    public function setDSTransID($value)
    {
        return $this->setParameter('DSTransID', $value);
    }

    public function getDSTransID()
    {
        return $this->getParameter('DSTransID');
    }
}
