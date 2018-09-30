<?php

namespace Omnipay\SagePay\Message\Form;

/**
 * Sage Pay Form Authorize/Purchase Response (form POST redirect).
 */

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\SagePay\ConstantsInterface;

class Response extends AbstractResponse implements RedirectResponseInterface, ConstantsInterface
{
    /**
     * @var string Endpoint base URLs.
     */
    protected $liveEndpoint = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
    protected $testEndpoint = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';

    /**
     * Always a redirect, so not yet successful.
     */
    public function isSuccessful()
    {
        return false;
    }

    public function isRedirect()
    {
        return true;
    }

    public function getRedirectMethod()
    {
        return 'POST';
    }

    public function getRedirectData()
    {
        return array_intersect_key(
            $this->getData(),
            array_flip([
                'VPSProtocol',
                'TxType',
                'Vendor',
                'Crypt',
            ])
        );
    }

    /**
     * @return string URL to 3D Secure endpoint.
     */
    public function getRedirectUrl()
    {
        if ($this->getTestMode()) {
            return $this->testEndpoint;
        }

        return $this->liveEndpoint;
    }

    public function getTestMode()
    {
        $data =  $this->getData();

        return !empty($data['TestMode']);
    }
}
