<?php

namespace Omnipay\SagePay;

use Omnipay\Common\AbstractGateway as OmnipayAbstractGateway;
use Omnipay\SagePay\Traits\GatewayParamsTrait;

abstract class AbstractGateway extends OmnipayAbstractGateway implements ConstantsInterface
{
    use GatewayParamsTrait;

    /**
     * Examples for language: EN, DE and FR.
     * Also supports a locale format.
     */
    public function getDefaultParameters()
    {
        return [
            'vendor' => null,
            'testMode' => false,
            'referrerId' => null,
            'language' => null,
            'useOldBasketFormat' => false,
            'exitOnResponse' => false,
            'apply3DSecure' => null,
            'useAuthenticate' => null,
            'accountType' => null,
        ];
    }
}
