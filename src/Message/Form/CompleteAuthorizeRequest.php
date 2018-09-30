<?php

namespace Omnipay\SagePay\Message\Form;

/**
 * Sage Pay Form Complete Authorize Response.
 */

use Omnipay\SagePay\Message\AbstractRequest;
use Omnipay\SagePay\Message\Response;

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
        $crypt = $this->httpRequest->query->get('crypt');

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
     * Nothing to send - we have the result data in the server request.
     */
    public function sendData($data)
    {
        return $this->response = new Response($this, $data);
    }
}
