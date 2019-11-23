<?php

namespace Omnipay\SagePay\Message\Form;

/**
 * Sage Pay Form Complete Authorize Response.
 */

use Omnipay\SagePay\Message\AbstractRequest;
use Omnipay\SagePay\Message\Response as GenericResponse;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Exception\InvalidRequestException;

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
     *
     * @return array
     * @throws InvalidResponseException if "crypt" is missing or invalid.
     */
    public function getData()
    {
        // The application has the option of passing the query parameter
        // in, perhaps using its own middleware, or allowing Omnipay to
        // provide it.

        $crypt = $this->getCrypt() ?: $this->httpRequest->query->get('crypt');

        // Make sure we have a crypt parameter before trying to decrypt it.

        if (empty($crypt) || !is_string($crypt) || substr($crypt, 0, 1) !== '@') {
            throw new InvalidResponseException('Missing or invalid "crypt" parameter');
        }

        // Remove the leading '@' and decrypt the remainder into a query string.
        // An InvalidResponseException is thrown if the crypt parameter data is not
        // a hexadecimal string.

        $hexString = substr($crypt, 1);

        if (! preg_match('/^[0-9a-f]+$/i', $hexString)) {
            throw new InvalidResponseException('Invalid "crypt" parameter; not hexadecimal');
        }

        $queryString = openssl_decrypt(
            hex2bin($hexString),
            'aes-128-cbc',
            $this->getEncryptionKey(),
            OPENSSL_RAW_DATA,
            $this->getEncryptionKey()
        );

        parse_str($queryString, $data);

        // The result will be ASCII data only, being a very restricted set of
        // IDs and flags, so can be treated as UTF-8 without any conversion.

        return($data);
    }

    /**
     * Nothing to send to gateway - we have the result data in the server request.
     *
     * @throws InvalidResponseException
     * @throws InvalidResponseException
     */
    public function sendData($data)
    {
        $this->response = new GenericResponse($this, $data);

        // Issue #131: confirm the response is for the transaction ID we are
        // expecting, and not replayed from another transaction.

        $originalTransactionId = $this->getTransactionId();
        $returnedTransactionId = $this->response->getTransactionId();

        if (empty($originalTransactionId)) {
            throw new InvalidRequestException('Missing expected transactionId parameter');
        }

        if ($originalTransactionId !== $returnedTransactionId) {
            throw new InvalidResponseException(sprintf(
                'Unexpected transactionId; expected "%s" received "%s"',
                $originalTransactionId,
                $returnedTransactionId
            ));
        }

        // The Response in the current namespace conflicts with
        // the Response in the namespace one level down, but only
        // for PHP 5.6. This alias works around it.

        return $this->response;
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
