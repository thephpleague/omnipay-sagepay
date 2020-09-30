<?php

namespace Omnipay\SagePay;

use Omnipay\Common\CreditCard;
use Omnipay\Common\Exception\InvalidCreditCardException;

/**
 * Class PayPal
 * @package Omnipay\Common
 */
class PayPal extends CreditCard
{
    /**
     * PayPal Brand
     *
     * Returns a card type of PayPal
     *
     * @return string
     */
    public function getBrand()
    {
        return 'paypal';
    }

    /**
     * PayPal payment does not require card detail validation
     *
     * @return void
     * @throws InvalidCreditCardException
     */
    public function validate()
    {
        $requiredParameters = array(
            'callbackUrl' => 'PayPal callback URL',
        );

        foreach ($requiredParameters as $key => $val) {
            if (!$this->getParameter($key)) {
                throw new InvalidCreditCardException("The $val is required");
            }
        }
    }

    /**
     * Get the card CVV.
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->getParameter('callbackUrl');
    }

    /**
     * Sets the card CVV.
     *
     * @param string $value
     * @return $this
     */
    public function setCallbackUrl($value)
    {
        return $this->setParameter('callbackUrl', $value);
    }
}