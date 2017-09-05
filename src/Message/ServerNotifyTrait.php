<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\NotificationInterface;

/**
 * Data access methods shared between the ServerNotificationRequest and
 * the ServerNotificationResponse.
 */

trait ServerNotifyTrait
{
    /**
     * The signature supplied with the data.
     */
    public function getSignature()
    {
        return strtolower($this->getDataItem('VPSSignature'));
    }

    /**
     * Create the signature calculated from the POST data and the saved SecurityKey
     * (the SagePay one-use signature).
     */
    public function buildSignature()
    {
        // Re-create the VPSSignature
        if ($this->getTxType() === Response::TXTYPE_TOKEN) {
            $signature_data = array(
                // For some bizarre reason, the VPSTxId is hashed at the Sage Pay gateway
                // without its curly brackets, so we must do the same to validate the hash.
                str_replace(array('{', '}'), '', $this->getVPSTxId()),
                // VendorTxCode
                $this->getTransactionId(),
                $this->getStatus(),
                strtolower($this->getVendor()),
                // Only returned for card tokenisation requests.
                $this->getToken(),
                // As saved in the merchant application.
                $this->getSecurityKey(),
            );
        } else {
            // Transaction types PAYMENT, DEFERRED and AUTHENTICATE (when suppoted)
            $signature_data = array(
                $this->getVPSTxId(),
                // VendorTxCode
                $this->getTransactionId(),
                $this->getStatus(),
                $this->getTxAuthNo(),
                strtolower($this->getVendor()),
                $this->getDataItem('AVSCV2'),
                // As saved in the merchant application.
                $this->getSecurityKey(),
                // Optional.
                $this->getDataItem('AddressResult'),
                $this->getDataItem('PostCodeResult'),
                $this->getDataItem('CV2Result'),
                $this->getDataItem('GiftAid'),
                $this->getDataItem('3DSecureStatus'),
                $this->getDataItem('CAVV'),
                $this->getDataItem('AddressStatus'),
                $this->getDataItem('PayerStatus'),
                $this->getDataItem('CardType'),
                $this->getLast4Digits(),
                // New for protocol v3.00
                $this->getDataItem('DeclineCode'),
                $this->getExpiryDate(),
                $this->getDataItem('FraudResponse'),
                $this->getDataItem('BankAuthCode'),
            );
        }

        return md5(implode('', $signature_data));
    }

    /**
     * Check whether the signature is valid.
     *
     * @return bool True if the signature is valid; false otherwise.
     */
    public function isValid()
    {
        return $this->getSignature() === $this->buildSignature();
    }

    /**
     * Was the transaction successful?
     *
     * @return string Transaction status, one of {@see STATUS_COMPLETED}, {@see #STATUS_PENDING},
     * or {@see #STATUS_FAILED}.
     */
    public function getTransactionStatus()
    {
        // If the signature check fails, then all bets are off - the POST cannot be trusted.
        if (! $this->isValid()) {
            return static::STATUS_FAILED;
        }

        $status = $this->getStatus();

        if ($status === Response::SAGEPAY_STATUS_OK) {
            return static::STATUS_COMPLETED;
        }

        if ($status === Response::SAGEPAY_STATUS_PENDING) {
            return static::STATUS_PENDING;
        }

        return static::STATUS_FAILED;
    }

    /**
     * The transaction type.
     */
    public function getTxType()
    {
        return $this->getDataItem('TxType');
    }

    public function getVPSTxId()
    {
        return $this->getDataItem('VPSTxId');
    }

    public function getTxAuthNo()
    {
        return $this->getDataItem('TxAuthNo');
    }

    /**
     * The VendorTxCode is POSTed - we will need this for looking up the transaction
     * locally.
     */
    public function getTransactionId()
    {
        return $this->getDataItem('VendorTxCode');
    }

    /**
     * Gateway Reference
     *
     * @return string A reference provided by the gateway to represent this transaction
     */
    public function getTransactionReference()
    {
        $reference = array();
        $reference['SecurityKey'] = $this->getSecurityKey();

        foreach (array('VendorTxCode', 'TxAuthNo', 'VPSTxId') as $key) {
            $reference[$key] = $this->getDataItem($key);
        }

        ksort($reference);

        return json_encode($reference);
    }

    /**
     * Get the card type, as supplied in raw format by the gateway.
     *
     * @return string
     */
    public function getCardType()
    {
        return $this->getDataItem('CardType');
    }

    /**
     * This should probably be the numeric code embedded in the StatusDetail,
     * but this is a good approximation.
     */
    public function getCode()
    {
        return $this->getStatus();
    }

    /**
     * Raw expiry date for the card, "MMYY" format by default.
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

    /**
     * Last four digits of the card used.
     */
    public function getLast4Digits()
    {
        return $this->getDataItem('Last4Digits');
    }

    /**
     * Alias for getLast4Digits() using Omnipay parlance.
     */
    public function getNumberLastFour()
    {
        return $this->getLast4Digits();
    }

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
}
