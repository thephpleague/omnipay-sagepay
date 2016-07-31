<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\NotificationInterface;

/**
 * Sage Pay Server Notification.
 * The gateway will send the results of Server transactions here.
 */
class ServerNotifyAuthorizeRequest extends AbstractRequest implements NotificationInterface
{
    /**
     * Raw Status values.
     * TODO: move these so they can be shared with the Response message.
     */
    const SAGEPAY_STATUS_OK = 'OK';
    const SAGEPAY_STATUS_PENDING = 'PENDING';
    const SAGEPAY_STATUS_NOTAUTHED = 'NOTAUTHED';
    const SAGEPAY_STATUS_REJECTED = 'REJECTED';
    const SAGEPAY_STATUS_ABORT = 'ABORT';
    const SAGEPAY_STATUS_ERROR = 'ERROR';

    const ADDRESS_RESULT_NOTPROVIDED = 'NOTPROVIDED';
    const ADDRESS_RESULT_NOTCHECKED = 'NOTCHECKED';
    const ADDRESS_RESULT_MATCHED = 'MATCHED';
    const ADDRESS_RESULT_NOTMATCHED = 'NOTMATCHED';

    const POSTCODE_RESULT_NOTPROVIDED = 'NOTPROVIDED';
    const POSTCODE_RESULT_NOTCHECKED = 'NOTCHECKED';
    const POSTCODE_RESULT_MATCHED = 'MATCHED';
    const POSTCODE_RESULT_NOTMATCHED = 'NOTMATCHED';

    const CV2_RESULT_NOTPROVIDED = 'NOTPROVIDED';
    const CV2_RESULT_NOTCHECKED = 'NOTCHECKED';
    const CV2_RESULT_MATCHED = 'MATCHED';
    const CV2_RESULT_NOTMATCHED = 'NOTMATCHED';

    const GIFTAID_CHECKED_TRUE = '1';
    const GIFTAID_CHECKED_FALSE = '0';

    const SECURE3D_STATUS_OK = 'OK';
    const SECURE3D_STATUS_NOTCHECKED = 'NOTCHECKED';
    const SECURE3D_STATUS_NOTAVAILABLE = 'NOTAVAILABLE';
    const SECURE3D_STATUS_NOTAUTHED = 'NOTAUTHED';
    const SECURE3D_STATUS_INCOMPLETE = 'INCOMPLETE';
    const SECURE3D_STATUS_ATTEMPTONLY = 'ATTEMPTONLY';
    const SECURE3D_STATUS_ERROR = 'ERROR';

    const ADDRESS_STATUS_NONE = 'NONE';
    const ADDRESS_STATUS_CONFIRMED = 'CONFIRMED';
    const ADDRESS_STATUS_UNCONFIRMED = 'UNCONFIRMED';

    const PAYER_STATUS_VERIFIED = 'VERIFIED';
    const PAYER_STATUS_UNVERIFIED = 'UNVERIFIED';

    // TODO: a translation to OmniPay card brands would be useful.
    const CARDTYPE_VISA = 'VISA';
    const CARDTYPE_MC = 'MC';
    const CARDTYPE_MCDEBIT = 'MCDEBIT';
    const CARDTYPE_DELTA = 'DELTA';
    const CARDTYPE_MAESTRO = 'MAESTRO';
    const CARDTYPE_UKE = 'UKE';
    const CARDTYPE_AMEX = 'AMEX';
    const CARDTYPE_DC = 'DC';
    const CARDTYPE_JCB = 'JCB';
    const CARDTYPE_PAYPAL = 'PAYPAL';

    const FRAUD_RESPONSE_ACCEPT = 'ACCEPT';
    const FRAUD_RESPONSE_CHALLENGE = 'CHALLENGE';
    const FRAUD_RESPONSE_DENY = 'DENY';
    const FRAUD_RESPONSE_NOTCHECKED = 'NOTCHECKED';

    /**
     * Copy of the POST data sent in.
     */
    protected $data;

    public function getData()
    {
        // Grab the data from the request if we don't already have it.
        // This would be a doog place to convert the encoding if required
        // e.g. ISO-8859-1 to UTF-8.

        if (!isset($this->data)) {
            $this->data = $this->httpRequest->request->all();
        }

        return $this->data;
    }

    /**
     * Get a POST data item, or '' if not set.
     */
    protected function getDataItem($name)
    {
        $data = $this->getData();

        return isset($this->data[$name]) ? $this->data[$name] : '';
    }

    /**
     * The raw status code.
     */
    public function getStatus()
    {
        return $this->getDataItem('Status');
    }

    public function getCode()
    {
        return $this->getStatus();
    }

    /**
     * The signature supplied with the data.
     */
    public function getSignature()
    {
        return strtolower($this->getDataItem('VPSSignature'));
    }

    /**
     * Create the signature calculated from the POST data and the saved SecurityKey
     * (the SagePay one-use token).
     */
    public function buildSignature()
    {
        // Re-create the VPSSignature
        $signature_data = array(
            $this->getVPSTxId(),
            $this->getTransactionId(), // VendorTxCode
            $this->getStatus(),
            $this->getTxAuthNo(),
            $this->getVendor(),
            $this->getDataItem('AVSCV2'),
            $this->getSecurityKey(), // Saved
            $this->getDataItem('AddressResult'),
            $this->getDataItem('PostCodeResult'),
            $this->getDataItem('CV2Result'),
            $this->getDataItem('GiftAid'),
            $this->getDataItem('3DSecureStatus'),
            $this->getDataItem('CAVV'),
            $this->getDataItem('AddressStatus'),
            $this->getDataItem('PayerStatus'),
            $this->getDataItem('CardType'),
            $this->getDataItem('Last4Digits'),
            // New for protocol v3.00
            $this->getDataItem('DeclineCode'),
            $this->getDataItem('ExpiryDate'), // Format: MMYY
            $this->getDataItem('FraudResponse'),
            $this->getDataItem('BankAuthCode'),
        );

        return md5(implode('', $signature_data));
    }

    /**
     * Check whether the ignature is valid.
     */
    public function checkSignature()
    {
        return $this->getSignature() == $this->buildSignature();
    }

    /**
     * Set the saved TransactionReference.
     * We are only interested in extracting the security key here.
     * It makes more sense to use setSecurityKey().
     *
     * @return self
     */
    public function setTransactionReference($reference)
    {
        // Is this a JSON string?
        if (strpos($reference, 'SecurityKey') !== false) {
            // Yes. Decode it then extact the security key.

            $parts = json_decode($reference, true);

            if (isset($parts['SecurityKey'])) {
                $this->setSecurityKey($parts['SecurityKey']);
            }
        }
    }

    /**
     * Gateway Reference
     *
     * @return string A reference provided by the gateway to represent this transaction
     */
    public function getTransactionReference()
    {
        $reference = array();
        $reference['VendorTxCode'] = $this->getTransactionId();

        foreach (array('SecurityKey', 'TxAuthNo', 'VPSTxId') as $key) {
            $reference[$key] = $this->getDataItem$key);
        }

        ksort($reference);

        return json_encode($reference);
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
     * Set the SecurityKey that we saved locally.
     *
     * @return self
     */
    public function setSecurityKey($value)
    {
        return $this->setParameter('SecurityKey', $value);
    }

    public function getSecurityKey()
    {
        return $this->getParameter('SecurityKey');
    }

    // This comes from the POSTed data, not anything we have set.
    public function getVPSTxId()
    {
        return $this->getDataItem('VPSTxId');
    }

    public function getTxAuthNo()
    {
        return $this->getDataItem('TxAuthNo');
    }

    /**
     * Was the transaction successful?
     *
     * @return string Transaction status, one of {@see STATUS_COMPLETED}, {@see #STATUS_PENDING},
     * or {@see #STATUS_FAILED}.
     */
    public function getTransactionStatus()
    {
        $status = $this->getStatus();

        if (!$this->checkSignature()) {
            return static::STATUS_ERROR;
        }

        if ($status == static::SAGEPAY_STATUS_OK) {
            return static::STATUS_COMPLETED;
        }

        if ($status == static::SAGEPAY_STATUS_PENDINF) {
            return static::STATUS_PENDING;
        }

        return static::STATUS_FAILED;
    }

    /**
     * Response Message
     *
     * @return string A response message from the payment gateway
     */
    public function getMessage()
    {
        return $this->getDataItem('StatusDetail');
    }
}
