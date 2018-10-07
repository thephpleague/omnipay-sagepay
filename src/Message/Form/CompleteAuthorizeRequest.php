<?php

namespace Omnipay\SagePay\Message\Form;

/**
 * Sage Pay Form Complete Authorize Response.
 */

use Omnipay\SagePay\Message\AbstractRequest;
use Omnipay\SagePay\Message\Response as GenericResponse;

class CompleteAuthorizeRequest extends AbstractRequest
{
    /**
     * @return string the transaction type
     */
    public function getTxType()
    {
        if ($this->getUseAuthenticate()) {
            return static::TXTYPE_AUTHORISE;
        } else {
            return static::TXTYPE_RELEASE;
        }
    }

    /**
     * Data will be encrypted as a query parameter.
     */
    public function getData()
    {
        $crypt = $this->getCrypt() ?: $this->httpRequest->query->get('crypt');

        // Remove the leading '@' and decrypt.

        $query = openssl_decrypt(
            hex2bin(substr($crypt, 1)),
            'aes-128-cbc',
            $this->getEncryptionKey(),
            OPENSSL_RAW_DATA,
            $this->getEncryptionKey()
        );

        parse_str($query, $data);

        return($data);
    }

    /**
     * Nothing to send to gateway - we have the result data in the server request.
     */
    public function sendData($data)
    {
        // The Response in the current namespace conflicts with
        // the Response in the namespace one level down, but only
        // for PHP 5.6. This alias works around it.

        return $this->response = new GenericResponse($this, $data);
    }

    /**
     * @return string The crypt set as an override for the query parameter.
     */
    public function getCrypt()
    {
        return $this->getParameter('cryptx');
    }

    /**
     * @param string $value If set, then used in preference to the current query parameter.
     * @return $this
     */
    public function setCrypt($value)
    {
        return $this->setParameter('cryptx', $value);
    }
}
