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
    use CardResponseFieldsTrait;

    /**
     * Raw Status values.
     */

    const TXTYPE_PAYMENT        = 'PAYMENT';
    const TXTYPE_DEFERRED       = 'DEFERRED';
    const TXTYPE_AUTHENTICATE   = 'AUTHENTICATE';
    const TXTYPE_TOKEN          = 'TOKEN';

    const SAGEPAY_STATUS_OK         = 'OK';
    const SAGEPAY_STATUS_PENDING    = 'PENDING';
    const SAGEPAY_STATUS_NOTAUTHED  = 'NOTAUTHED';
    const SAGEPAY_STATUS_REJECTED   = 'REJECTED';
    const SAGEPAY_STATUS_ABORT      = 'ABORT';
    const SAGEPAY_STATUS_ERROR      = 'ERROR';

    const ADDRESS_RESULT_NOTPROVIDED    = 'NOTPROVIDED';
    const ADDRESS_RESULT_NOTCHECKED     = 'NOTCHECKED';
    const ADDRESS_RESULT_MATCHED        = 'MATCHED';
    const ADDRESS_RESULT_NOTMATCHED     = 'NOTMATCHED';

    const POSTCODE_RESULT_NOTPROVIDED   = 'NOTPROVIDED';
    const POSTCODE_RESULT_NOTCHECKED    = 'NOTCHECKED';
    const POSTCODE_RESULT_MATCHED       = 'MATCHED';
    const POSTCODE_RESULT_NOTMATCHED    = 'NOTMATCHED';

    const CV2_RESULT_NOTPROVIDED        = 'NOTPROVIDED';
    const CV2_RESULT_NOTCHECKED         = 'NOTCHECKED';
    const CV2_RESULT_MATCHED            = 'MATCHED';
    const CV2_RESULT_NOTMATCHED         = 'NOTMATCHED';

    const GIFTAID_CHECKED_TRUE  = '1';
    const GIFTAID_CHECKED_FALSE = '0';

    const SECURE3D_STATUS_OK            = 'OK';
    const SECURE3D_STATUS_NOTCHECKED    = 'NOTCHECKED';
    const SECURE3D_STATUS_NOTAVAILABLE  = 'NOTAVAILABLE';
    const SECURE3D_STATUS_NOTAUTHED     = 'NOTAUTHED';
    const SECURE3D_STATUS_INCOMPLETE    = 'INCOMPLETE';
    const SECURE3D_STATUS_ATTEMPTONLY   = 'ATTEMPTONLY';
    const SECURE3D_STATUS_ERROR         = 'ERROR';

    const ADDRESS_STATUS_NONE           = 'NONE';
    const ADDRESS_STATUS_CONFIRMED      = 'CONFIRMED';
    const ADDRESS_STATUS_UNCONFIRMED    = 'UNCONFIRMED';

    const PAYER_STATUS_VERIFIED     = 'VERIFIED';
    const PAYER_STATUS_UNVERIFIED   = 'UNVERIFIED';

    // TODO: a translation to OmniPay card brands would be useful.
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

    public function isSuccessful()
    {
        return isset($this->data['Status']) && 'OK' === $this->data['Status'];
    }

    /**
     * The only reason supported for a redirect from a Server transaction
     * will be 3D Secure. PayPal may come into this at some point.
     */
    public function isRedirect()
    {
        return isset($this->data['Status']) && '3DAUTH' === $this->data['Status'];
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
     */
    public function getTransactionReference()
    {
        $reference = array();
        $reference['VendorTxCode'] = $this->getRequest()->getTransactionId();

        foreach (array('SecurityKey', 'TxAuthNo', 'VPSTxId') as $key) {
            if (isset($this->data[$key])) {
                $reference[$key] = $this->data[$key];
            }
        }

        ksort($reference);

        return json_encode($reference);
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

    public function getRedirectMethod()
    {
        return 'POST';
    }

    /**
     * The usual reason for a redirect is for a 3D Secure check.
     * @return array 3D Secure POST data.
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
}
