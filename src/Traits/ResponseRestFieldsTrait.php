<?php

namespace Omnipay\SagePay\Traits;

/**
 * Response fields shared between the Direct/Server response class and
 * the notification handler.
 */

trait ResponseRestFieldsTrait
{
    /**
     * Get a POST data item, or null if not present.
     *
     * @param  string $name    The key for the field.
     * @param  mixed $default  The value to return if the data item is not found at all, or is null.
     * @return mixed           The value of the field, often a string, but could be case to anything..
     */
    protected function getDataItem($name, $default = null)
    {
        $data = $this->getData();

        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }

    /**
     * @return bool True if the transaction is successful and complete.
     */
    public function isSuccessful()
    {
        return $this->getStatus() === static::SAGEPAY_STATUS_OK
            || $this->getStatus() === static::SAGEPAY_STATUS_OK_REPEATED
            || $this->getStatus() === static::SAGEPAY_STATUS_REGISTERED
            || $this->getStatus() === static::SAGEPAY_STATUS_AUTHENTICATED;
    }

    /**
     * Get the cardReference generated when creating a card reference
     * during an authorisation or payment, or as an explicit request.
     *
     * @return string Currently an md5 format token.
     */
    public function getCardReference()
    {
        return $this->getToken();
    }

    /**
     * A card token is returned if one has been requested.
     *
     * @return string Currently an md5 format token.
     */
    public function getToken()
    {
        return $this->getDataItem('Token');
    }

    /**
     * The raw status code.
     *
     * @return string One of static::SAGEPAY_STATUS_*
     */
    public function getStatus()
    {
        return strtoupper($this->getDataItem('status'));
    }

    /**
     * The raw status code.
     *
     * @return string One of static::SAGEPAY_STATUS_*
     */
    public function getCode()
    {
        return $this->getStatusCode() ?? $this->getDataItem('code') ?? 'No Code';
    }

    /**
     * The raw status code.
     *
     * @return string One of static::SAGEPAY_STATUS_*
     */
    public function getStatusCode()
    {
        return $this->getDataItem('statusCode');
    }

    /**
     * Response Textual Message
     *
     * @return string A response message from the payment gateway
     */
    public function getMessage()
    {
        return $this->getStatusDetail() ?? $this->getDataItem('description');
    }

    /**
     * Response Textual Message
     *
     * @return string A response message from the payment gateway
     */
    public function getStatusDetail()
    {
        return $this->getDataItem('statusDetail');
    }

    /**
     * Sage Pay unique Authorisation Code for a successfully authorised transaction.
     * Only present if Status is OK
     *
     * @return string
     */
    public function getTxAuthNo()
    {
        return $this->getRetrievalReference();
    }

    /**
     * Sage Pay unique Authorisation Code for a successfully authorised transaction.
     * Only present if Status is OK
     *
     * @return string
     */
    public function getRetrievalReference()
    {
        return $this->getDataItem('retrievalReference');
    }

    /**
     * Sage Pay unique Authorisation Code for a successfully authorised transaction.
     * Only present if Status is OK
     *
     * @return array
     */
    public function getAvsCvcCheck($wholeStatus = true)
    {
        $avsCvcCheck = $this->getDataItem('avsCvcCheck');

        if (array_key_exists('status', $avsCvcCheck) && $wholeStatus) {
            return $avsCvcCheck['status'];
        }
        return (object) $avsCvcCheck;
    }
    
    /**
     * This is the response from AVS and CV2 checks.
     * Provided for Vendor info and backward compatibility with the
     * banks. Rules set up in MySagePay will accept or reject
     * the transaction based on these values.
     *
     * More detailed results are split out in the next three fields:
     * AddressResult, PostCodeResult and CV2Result.
     *
     * Not present if the Status is:
     * 3DAUTH, AUTHENTICATED, PPREDIRECT or REGISTERED.
     *
     * @return string One of static::AVSCV2_RESULT_*
     */
    public function getAVSCV2()
    {
        return strtoupper($this->getAvsCvcCheck());
    }

    /**
     * The specific result of the checks on the cardholder’s
     * address numeric from the AVS/CV2 checks.
     *
     * @return string Once of static::ADDRESS_RESULT_*
     */
    public function getAddressResult()
    {
        return strtoupper($this->getAvsCvcCheck(false)->address);
    }

    /**
     * The specific result of the checks on the cardholder’s
     * Postcode from the AVS/CV2 checks.
     *
     * @return string Once of static::POSTCODE_RESULT_*
     */
    public function getPostCodeResult()
    {
        return strtoupper($this->getAvsCvcCheck(false)->postalCode);
    }

    /**
     * The specific result of the checks on the cardholder’s CV2
     * code from the AVS/CV2 checks.
     *
     * @return string One of static::CV2_RESULT_*
     */
    public function getCV2Result()
    {
        return strtoupper($this->getAvsCvcCheck(false)->securityCode);
    }

    /**
     * This field details the results of the 3D-Secure checks
     * where appropriate.
     *
     * @return string One of static::SECURE3D_STATUS_*
     */
    public function get3DSecureStatus()
    {
        $secure3DResponse = $this->getDataItem('3DSecure');

        if (array_key_exists('status', $secure3DResponse)) {
            return strtoupper($secure3DResponse['status']);
        }

        return $secure3DResponse;
    }

    /**
     * The encoded result code from the 3D-Secure checks (CAVV or UCAF).
     * Only present if the 3DSecureStatus field is OK or ATTEMPTONLY.
     *
     * @return string Up to 32 characters long.
     */
    public function getCAVV()
    {
        return $this->getDataItem('CAVV');
    }

    /**
     * The raw frawd response from the gateway.
     *
     * @return string One of static::FRAUD_RESPONSE_*
     */
    public function getFraudResponse()
    {
        return $this->getDataItem('FraudResponse');
    }

    /**
     * The authorisation code returned from the bank. e.g. T99777
     * @return string
     */
    public function getBankAuthCode()
    {
        return $this->getDataItem('BankAuthCode');
    }

    /**
     * The decline code from the bank. These codes are
     * specific to the bank. Please contact them for a description
     * of each code. e.g. 00
     * @return string Two digit code, specific to the bacnk.
     */
    public function getDeclineCode()
    {
        return $this->getDataItem('DeclineCode');
    }

    /**
     * Returns the surcharge amount charged and is only
     * present if a surcharge was applied to the transaction.
     * The surcharge should be added to the original requested amount
     * to give the total amount authorised for payment.
     *
     * @return string|null Contains a floating point number.
     */
    public function getSurcharge()
    {
        return $this->getDataItem('Surcharge');
    }

    /**
     * Raw expiry date for the card, "MMYY" format by default.
     * The expiry date is available for Sage Pay Direct responses, even if the
     * remaining card details are not.
     * Also supports custom formats.
     *
     * @param  string|null $format Format using the PHP date() format string.
     * @return string
     */
    public function getExpiryDate($format = null)
    {
        $expiryDate = $this->getDataItem('ExpiryDate');

        if ($format === null || $expiryDate === null) {
            return $expiryDate;
        } else {
            return gmdate(
                $format,
                gmmktime(0, 0, 0, $this->getExpiryMonth(), 1, $this->getExpiryYear())
            );
        }
    }

    /**
     * Get the card expiry month.
     *
     * @return int The month number, 1 to 12.
     */
    public function getExpiryMonth()
    {
        $expiryDate = $this->getDataItem('ExpiryDate');

        if (! empty($expiryDate)) {
            return (int)substr($expiryDate, 0, 2);
        }
    }

    /**
     * Get the card expiry year.
     *
     * @return int The full four-digit year.
     */
    public function getExpiryYear()
    {
        $expiryDate = $this->getDataItem('ExpiryDate');

        if (! empty($expiryDate)) {
            // COnvert 2-digit year to 4-dogot year, in 1970-2069 range.
            $dateTime = \DateTime::createFromFormat('y', substr($expiryDate, 2, 2));
            return (int)$dateTime->format('Y');
        }
    }

    /**
     * The transaction ID will be returned in the data for the Form API, or
     * we will have to refer to the request for the Server and Direct APIs.
     *
     * @return @inherit
     */
    public function getTransactionId()
    {
        return $this->getDataItem('VendorTxCode')
            ?: $this->getRequest()->getTransactionId();
    }
}
