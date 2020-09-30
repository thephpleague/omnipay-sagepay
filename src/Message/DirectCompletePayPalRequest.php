<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidRequestException;

/**
 * Sage Pay Direct Complete PayPal Request.
 */
class DirectCompletePayPalRequest extends AbstractRequest
{
    /**
     * @return string
     */
    public function getService()
    {
        return static::SERVICE_PAYPAL;
    }

    /**
     * @return string
     */
    public function getTxType()
    {
        return static::TXTYPE_COMPLETE;
    }

    /**
     * @return array|mixed
     * @throws InvalidRequestException
     */
    public function getData()
    {
        return $this->getBaseAuthorizeData();
    }

    /**
     * The required fields concerning what is being authorised and who
     * it is being authorised for.
     *
     * @return array
     * @throws InvalidRequestException
     */
    protected function getBaseAuthorizeData()
    {
        $this->validate('transactionId', 'amount', 'accept');

        // Start with the authorisation and API version details.
        $data = $this->getBaseData();

        // Money formatted as major unit decimal.
        $data['VPSTxId'] = $this->getTransactionId();
        $data['Amount'] = $this->getAmount();
        $data['Accept'] = !$this->getAccept() || strtoupper($this->getAccept()) === 'NO' ? 'NO' : 'YES';

        return $data;
    }

    /**
     * @return string
     */
    public function getAccept()
    {
        return $this->getParameter('accept');
    }

    /**
     * Override the MD passed into the current request.
     *
     * @param string $value
     * @return $this
     */
    public function setAccept($value)
    {
        return $this->setParameter('accept', $value);
    }
}