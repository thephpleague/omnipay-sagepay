<?php

namespace Omnipay\SagePay\Traits;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\SagePay\Message\Response;

/**
 * Data access methods shared between the ServerNotificationRequest and
 * the ServerNotificationResponse.
 */

trait ServerNotifyTrait
{
    /**
     * The signature supplied with the data, made lower case.
     */
    public function getSignature()
    {
        return strtolower($this->getDataItem('VPSSignature'));
    }

    /**
     * Create the signature calculated from the POST data and the saved SecurityKey.
     * This signature is lower case.
     */
    public function buildSignature()
    {
        // Re-create the VPSSignature.
        // Note that an ABORT notification for a TOKEN request type flips back to
        // the transaction format. We'll try merging these.

        $VPSTxId = $this->getVPSTxId();

        if ($this->getTxType() === Response::TXTYPE_TOKEN
            && $this->getStatus() === Response::SAGEPAY_STATUS_OK
        ) {
            // For some bizarre reason, the VPSTxId is hashed at the Sage Pay gateway
            // without its curly brackets, so we must do the same to validate the hash.
            // This only happens for a valid TOKEN request, and not for an aborted
            // TOKEN request.
            // The successful TOKEN request also does not include the card details, even
            // though they are present. The ABORTed token request does include the address
            // result details in the signature, even though they are no relevant.

            $VPSTxId = str_replace(['{', '}'], '', $VPSTxId);
        }

        // Transaction types PAYMENT, DEFERRED and AUTHENTICATE (when suppoted)
        // and non-transaction TOKEN request.

        $signatureData = array(
            $VPSTxId,
            // VendorTxCode
            $this->getTransactionId(),
            $this->getStatus(),
            $this->getTxAuthNo(),
            strtolower($this->getVendor()),
            $this->getAVSCV2(),
            ($this->getTxType() === Response::TXTYPE_TOKEN ? $this->getToken() : ''),
            // As saved in the merchant application.
            $this->getSecurityKey(),
        );

        if ($this->getTxType() !== Response::TXTYPE_TOKEN
            || $this->getStatus() !== Response::SAGEPAY_STATUS_OK
        ) {
            // Do not use any of these fields for a successful TOKEN transaction,
            // even though some of them may be present.

            $signatureData = array_merge(
                $signatureData,
                array(
                    // Details for AVSCV2:
                    $this->getAddressResult(),
                    $this->getPostCodeResult(),
                    $this->getCV2Result(),
                    //
                    $this->getGiftAid(),
                    $this->get3DSecureStatus(),
                    $this->getCAVV(),
                    $this->getAddressStatus(),
                    $this->getPayerStatus(),
                    $this->getCardType(),
                    $this->getLast4Digits(),
                    // New for protocol v3.00
                    $this->getDeclineCode(),
                    $this->getExpiryDate(),
                    $this->getFraudResponse(),
                    $this->getBankAuthCode(),
                )
            );
        }

        return md5(implode('', $signatureData));
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

        if ($status === Response::SAGEPAY_STATUS_OK
            || $status === Response::SAGEPAY_STATUS_OK_REPEATED
            || $status === Response::SAGEPAY_STATUS_AUTHENTICATED
            || $status === Response::SAGEPAY_STATUS_REGISTERED
        ) {
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

    /**
     * Gateway Reference.
     *
     * @return string|null A reference provided by the gateway to represent this transaction
     */
    public function getTransactionReference()
    {
        $reference = [];

        foreach (['TxAuthNo', 'VPSTxId'] as $key) {
            $value = $this->getDataItem($key);

            if ($value !== null) {
                $reference[$key] = $value;
            }
        }

        // If there is no auth number or VPS transaction ID, then
        // there is no reference to speak of; return null.

        if (empty($reference)) {
            return;
        }

        // The security key is passed in as a parameter by the application,
        // and not POSTed from the gateway.

        $reference['SecurityKey'] = $this->getSecurityKey();

        $reference['VendorTxCode'] = $this->getTransactionId();

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

    /*
     * This field is always present even if GiftAid is not active
     * on your account.
     */
    public function getGiftAid()
    {
        return $this->getDataItem('GiftAid');
    }

    /**
     * PayPal Transactions Only.
     * If AddressStatus is CONFIRMED and PayerStatus is VERIFIED,
     * the transaction may be eligible for PayPal Seller Protection.
     * To learn more about PayPal Seller Protection, please contact
     * PayPal directly or visit paypal.com
     */
    public function getAddressStatus()
    {
        return $this->getDataItem('AddressStatus');
    }

    /**
     * VERIFIED lets other members know the customer is a
     * confirmed PayPal member with a current, active bank
     * account, it also means the transaction may be eligible for
     * PayPal Seller Protection.
     */
    public function getPayerStatus()
    {
        return $this->getDataItem('PayerStatus');
    }
}
