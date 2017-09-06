<?php

namespace Omnipay\SagePay\Message;

/**
 * Response fields shared between the Direct/Server response class and
 * the notification handler.
 */

trait ResponseFieldsTrait
{
    /**
     * Get a POST data item, or null if not present.
     */
    protected function getDataItem($name, $default = null)
    {
        $data = $this->getData();

        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }

    /**
     * Get the cardReference generated when creating a card reference
     * during an authorisation or payment, or as an explicit request.
     */
    public function getCardReference()
    {
        return $this->getToken();
    }

    /**
     * A card token is returned if one has been requested.
     */
    public function getToken()
    {
        return $this->getDataItem('Token');
    }

    /**
     * The raw status code.
     */
    public function getStatus()
    {
        return $this->getDataItem('Status');
    }

    /**
     * Response Textual Message
     *
     * @return string A response message from the payment gateway
     */
    public function getMessage()
    {
        return $this->getDataItem('StatusDetail');
    }

    /**
     * Sage Pay unique Authorisation Code for a successfully authorised transaction.
     * Only present if Status is OK
     *
     * @return string
     */
    public function getTxAuthNo()
    {
        return $this->getDataItem('TxAuthNo');
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
     * @return string
     */
    public function getAVSCV2()
    {
        return $this->getDataItem('AVSCV2');
    }

    /**
     * The specific result of the checks on the cardholder’s
     * address numeric from the AVS/CV2 checks.
     */
    public function getAddressResult()
    {
        return $this->getDataItem('AddressResult');
    }

    /**
     * The specific result of the checks on the cardholder’s
     * Postcode from the AVS/CV2 checks.
     */
    public function getPostCodeResult()
    {
        return $this->getDataItem('PostCodeResult');
    }

    /**
     * The specific result of the checks on the cardholder’s CV2
     * code from the AVS/CV2 checks.
     */
    public function getCV2Result()
    {
        return $this->getDataItem('CV2Result');
    }

    /**
     * This field details the results of the 3D-Secure checks
     * where appropriate.
     */
    public function get3DSecureStatus()
    {
        return $this->getDataItem('3DSecureStatus');
    }

    /**
     * The encoded result code from the 3D-Secure checks (CAVV or UCAF).
     * Only present if the 3DSecureStatus field is OK or ATTEMPTONLY.
     */
    public function getCAVV()
    {
        return $this->getDataItem('CAVV');
    }

    /**
     * TBC
     */
    public function getFraudResponse()
    {
        return $this->getDataItem('FraudResponse');
    }

    /**
     * The authorisation code returned from the bank. e.g. T99777
     */
    public function getBankAuthCode()
    {
        return $this->getDataItem('BankAuthCode');
    }

    /**
     * The decline code from the bank. These codes are
     * specific to the bank. Please contact them for a description
     * of each code. e.g. 00
     */
    public function getDeclineCode()
    {
        return $this->getDataItem('DeclineCode');
    }

    /**
     * Raw expiry date for the card, "MMYY" format by default.
     * The expiry date is available for Sage Pay Direct responses, even if the
     * remaining card details are not.
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
     * @return int
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
     * @return int
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
}
