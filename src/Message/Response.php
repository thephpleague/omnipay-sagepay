<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;

/**
 * Sage Pay Response
 */
class Response extends AbstractResponse implements RedirectResponseInterface
{
    use ResponseFieldsTrait;

    /**
     * The raw transaction type the response is a part of
     * @var string
     */
    const TXTYPE_PAYMENT        = 'PAYMENT';
    const TXTYPE_DEFERRED       = 'DEFERRED';
    const TXTYPE_AUTHENTICATE   = 'AUTHENTICATE';
    const TXTYPE_TOKEN          = 'TOKEN';

    /**
     * There are a wide range of status codes across the different gatweay types
     * and in response to different types of request.
     * @var string
     */
    const SAGEPAY_STATUS_OK             = 'OK';
    const SAGEPAY_STATUS_OK_REPEATED    = 'OK REPEATED';
    const SAGEPAY_STATUS_PENDING        = 'PENDING';
    const SAGEPAY_STATUS_NOTAUTHED      = 'NOTAUTHED';
    const SAGEPAY_STATUS_REJECTED       = 'REJECTED';
    const SAGEPAY_STATUS_AUTHENTICATED  = 'AUTHENTICATED';
    const SAGEPAY_STATUS_REGISTERED     = 'REGISTERED';
    const SAGEPAY_STATUS_3DAUTH         = '3DAUTH';
    const SAGEPAY_STATUS_PPREDIRECT     = 'PPREDIRECT';
    const SAGEPAY_STATUS_ABORT          = 'ABORT';
    const SAGEPAY_STATUS_MALFORMED      = 'MALFORMED';
    const SAGEPAY_STATUS_INVALID        = 'INVALID';
    const SAGEPAY_STATUS_ERROR          = 'ERROR';

    /**
     * Raw values for AddressResult
     * @var string
     */
    const ADDRESS_RESULT_NOTPROVIDED    = 'NOTPROVIDED';
    const ADDRESS_RESULT_NOTCHECKED     = 'NOTCHECKED';
    const ADDRESS_RESULT_MATCHED        = 'MATCHED';
    const ADDRESS_RESULT_NOTMATCHED     = 'NOTMATCHED';

    /**
     * Raw values for PostCodeResult
     * @var string
     */
    const POSTCODE_RESULT_NOTPROVIDED   = 'NOTPROVIDED';
    const POSTCODE_RESULT_NOTCHECKED    = 'NOTCHECKED';
    const POSTCODE_RESULT_MATCHED       = 'MATCHED';
    const POSTCODE_RESULT_NOTMATCHED    = 'NOTMATCHED';

    /**
     * Raw values for CV2Result
     * @var string
     */
    const CV2_RESULT_NOTPROVIDED        = 'NOTPROVIDED';
    const CV2_RESULT_NOTCHECKED         = 'NOTCHECKED';
    const CV2_RESULT_MATCHED            = 'MATCHED';
    const CV2_RESULT_NOTMATCHED         = 'NOTMATCHED';

    /**
     * Raw values for AVSCV2
     * @var string
     */
    const AVSCV2_RESULT_ALLMATCH            = 'ALLMATCH';
    const AVSCV2_RESULT_SECURITY_CODE_ONLY  = 'SECURITY CODE MATCH ONLY';
    const AVSCV2_RESULT_ADDRESS_ONLY        = 'ADDRESS MATCH ONLY';
    const AVSCV2_RESULT_NO_DATA             = 'NO DATA MATCHES';
    const AVSCV2_RESULT_NOT_CHECKED         = 'DATA NOT CHECKED';

    /**
     * Raw values for GiftAidResult (Sage Pay Serverv only)
     * @var string
     */
    const GIFTAID_CHECKED_TRUE  = '1';
    const GIFTAID_CHECKED_FALSE = '0';

    /**
     * Raw results for 3DSecureStatus
     * @var string
     */
    const SECURE3D_STATUS_OK            = 'OK';
    const SECURE3D_STATUS_NOTCHECKED    = 'NOTCHECKED';
    const SECURE3D_STATUS_NOTAVAILABLE  = 'NOTAVAILABLE';
    const SECURE3D_STATUS_NOTAUTHED     = 'NOTAUTHED';
    const SECURE3D_STATUS_INCOMPLETE    = 'INCOMPLETE';
    const SECURE3D_STATUS_ATTEMPTONLY   = 'ATTEMPTONLY';
    const SECURE3D_STATUS_ERROR         = 'ERROR';
    const SECURE3D_STATUS_NOAUTH        = 'NOAUTH';
    const SECURE3D_STATUS_CANTAUTH      = 'CANTAUTH';
    const SECURE3D_STATUS_MALFORMED     = 'MALFORMED';
    const SECURE3D_STATUS_INVALID       = 'INVALID';

    /**
     * Raw results for AddressStatus (PayPal only)
     * @var string
     */
    const ADDRESS_STATUS_NONE           = 'NONE';
    const ADDRESS_STATUS_CONFIRMED      = 'CONFIRMED';
    const ADDRESS_STATUS_UNCONFIRMED    = 'UNCONFIRMED';

    /**
     * Raw results for PayerStatus (PayPal only)
     * @var string
     */
    const PAYER_STATUS_VERIFIED     = 'VERIFIED';
    const PAYER_STATUS_UNVERIFIED   = 'UNVERIFIED';

    /**
     * The raw recorded card type that was used (Sage Pay Server).
     * TODO: a translation to OmniPay card brands would be useful.
     * @var string
     */
    const CARDTYPE_VISA     = 'VISA';
    const CARDTYPE_MC       = 'MC';
    const CARDTYPE_MCDEBIT  = 'MCDEBIT';
    const CARDTYPE_DELTA    = 'DELTA';
    const CARDTYPE_MAESTRO  = 'MAESTRO';
    const CARDTYPE_UKE      = 'UKE';
    const CARDTYPE_AMEX     = 'AMEX';
    const CARDTYPE_DC       = 'DC';
    const CARDTYPE_JCB      = 'JCB';
    const CARDTYPE_PAYPAL   = 'PAYPAL';

    /**
     * The raw FraudResponse values.
     * @var string
     */
    const FRAUD_RESPONSE_ACCEPT     = 'ACCEPT';
    const FRAUD_RESPONSE_CHALLENGE  = 'CHALLENGE';
    const FRAUD_RESPONSE_DENY       = 'DENY';
    const FRAUD_RESPONSE_NOTCHECKED = 'NOTCHECKED';

    /**
     * FIXME: The response should never be directly passed the raw HTTP
     * body like this. The body should be parsed to data before instantiation.
     * However, the tests do not do that. I believe it is the tests that are broken,
     * but the tests are how the interface has been implemented so we cannot break
     * that for people who may rely on it.
     */
    public function __construct(RequestInterface $request, $data)
    {
        $this->request = $request;

        if (!is_array($data)) {
            // Split the data (string or guzzle body object) into lines.
            $lines = preg_split('/[\n\r]+/', (string)$data);

            $data = array();

            foreach ($lines as $line) {
                $line = explode('=', $line, 2);
                if (!empty($line[0])) {
                    $data[trim($line[0])] = isset($line[1]) ? trim($line[1]) : '';
                }
            }
        }

        $this->data = $data;
    }

    /**
     * CHECKME: should we include "OK REPEATED" as a successful status too?
     *
     * @return bool True if the transaction is successful and complete.
     */
    public function isSuccessful()
    {
        return $this->getStatus() === static::SAGEPAY_STATUS_OK;
    }

    /**
     * Gateway Reference
     *
     * Sage Pay requires the original VendorTxCode as well as 3 separate
     * fields from the response object to capture or refund transactions at a later date.
     *
     * Active Merchant solves this dilemma by returning the gateway reference in the following
     * custom format: VendorTxCode;VPSTxId;TxAuthNo;SecurityKey
     *
     * We have opted to return this reference as JSON, as the keys are much more explicit.
     *
     * @return string JSON formatted data.
     */
    public function getTransactionReference()
    {
        $reference = array();
        $reference['VendorTxCode'] = $this->getRequest()->getTransactionId();

        foreach (array('SecurityKey', 'TxAuthNo', 'VPSTxId') as $key) {
            $value = $this->{'get' . $key}();
            if ($value !== null) {
                $reference[$key] = $value;
            }
        }

        ksort($reference);

        return json_encode($reference);
    }

    /**
     * The only reason supported for a redirect from a Server transaction
     * will be 3D Secure. PayPal may come into this at some point.
     *
     * @return bool True if a 3DSecure Redirect needs to be performed.
     */
    public function isRedirect()
    {
        return $this->getStatus() === static::SAGEPAY_STATUS_3DAUTH;
    }

    /**
     * @return string URL to 3D Secure endpoint.
     */
    public function getRedirectUrl()
    {
        if ($this->isRedirect()) {
            return $this->data['ACSURL'];
        }
    }

    /**
     * @return string The redirect method.
     */
    public function getRedirectMethod()
    {
        return 'POST';
    }

    /**
     * The usual reason for a redirect is for a 3D Secure check.
     * Note: when PayPal is supported, a different set of data will be returned.
     *
     * @return array Collected 3D Secure POST data.
     */
    public function getRedirectData()
    {
        if ($this->isRedirect()) {
            return array(
                'PaReq' => $this->data['PAReq'],
                'TermUrl' => $this->getRequest()->getReturnUrl(),
                'MD' => $this->data['MD'],
            );
        }
    }

    /**
     * The Sage Pay ID to uniquely identify the transaction on their system.
     * Only present if Status is OK or OK REPEATED.
     *
     * @return string
     */
    public function getVPSTxId()
    {
        return $this->getDataItem('VPSTxId');
    }

    /**
     * A hash used to sign the notification request sent direct to your
     * application.
     * The documentation states that this is used by Sage Pay Direct, but
     * I believe it is only used with Sage Pay Server.
     */
    public function getSecurityKey()
    {
        return $this->getDataItem('SecurityKey');
    }
}
