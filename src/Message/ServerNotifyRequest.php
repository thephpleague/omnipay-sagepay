<?php

namespace Omnipay\SagePay\Message;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\SagePay\Traits\ResponseFieldsTrait;
use Omnipay\SagePay\Traits\ServerNotifyTrait;

/**
 * Sage Pay Server Notification.
 * The gateway will send the results of Server transactions here.
 */
class ServerNotifyRequest extends AbstractRequest implements NotificationInterface
{
    use ResponseFieldsTrait;
    use ServerNotifyTrait;

    /**
     * Valid status responses, to return to the gateway.
     */
    const RESPONSE_STATUS_OK        = 'OK';
    const RESPONSE_STATUS_ERROR     = 'ERROR';
    const RESPONSE_STATUS_INVALID   = 'INVALID';

    /**
     * Line separator for return message to the gateway.
     */
    const LINE_SEP = "\r\n";

    /**
     * Copy of the POST data sent in.
     */
    protected $data;

    /**
     * Initialise the data from the server request.
     */
    public function __construct(ClientInterface $httpClient, HttpRequest $httpRequest)
    {
        parent::__construct($httpClient, $httpRequest);

        // Grab the data from the request if we don't already have it.
        // This would be a good place to convert the encoding if required
        // e.g. ISO-8859-1 to UTF-8.

        $this->data = $httpRequest->request->all();
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Legacy support.
     * We are only interested in extracting the security key here.
     * It makes more sense to use setSecurityKey().
     *
     * @return self
     */
    public function setTransactionReference($reference)
    {
        if (strpos($reference, 'SecurityKey') !== false) {
            // A JSON string provided - the legacy transactionReference format.
            // Decode it then extact the securityKey.
            // We only need the security key here for the signature; all other
            // items from the reference will be in the server request.

            $parts = json_decode($reference, true);

            if (isset($parts['SecurityKey'])) {
                $this->setSecurityKey($parts['SecurityKey']);
            }
        }

        return $this;
    }

    /**
     * Legacy support.
     *
     * @param mixed $data ignored
     * @return $this
     */
    public function sendData($data)
    {
        return $this;
    }

    /**
     * Set the SecurityKey that we saved locally.
     * This is our one-time secret for the signature hash.
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

    /**
     * Confirm
     *
     * Notify Sage Pay you received the payment details and wish to confirm the payment.
     *
     * @param string URL to forward the customer to.
     * @param string Optional human readable reasons for accepting the transaction.
     */
    public function confirm($nextUrl, $detail = null)
    {
        // If the signature is invalid, then do not allow the confirm.
        if (! $this->isValid()) {
            throw new InvalidResponseException('Cannot confirm an invalid notification');
        }

        $this->sendResponse(static::RESPONSE_STATUS_OK, $nextUrl, $detail);
    }

    /**
     * Alias for confirm(), trying to define some more general conventions.
     */
    public function accept($nextUrl, $detail = null)
    {
        return $this->confirm($nextUrl, $detail);
    }

    /**
     * Error
     *
     * Notify Sage Pay you received the payment details but there was an error and the payment
     * cannot be completed.
     *
     * @param string URL to foward the customer to.
     * @param string Optional human readable reasons for not accepting the transaction.
     */
    public function error($nextUrl, $detail = null)
    {
        // If the signature is invalid, then do not allow the reject.

        if (! $this->isValid()) {
            throw new InvalidResponseException('Cannot reject an invalid notification');
        }

        $this->sendResponse(static::RESPONSE_STATUS_ERROR, $nextUrl, $detail);
    }

    /**
     * Alias for error(), trying to define some more general conventions.
     */
    public function reject($nextUrl, $detail = null)
    {
        return $this->error($nextUrl, $detail);
    }

    /**
     * Invalid
     *
     * Notify Sage Pay you received *something* but the details were invalid and no payment
     * cannot be completed. Invalid should be called if you are not happy with the contents
     * of the POST, such as the MD5 hash signatures did not match or you do not wish to proceed
     * with the order.
     *
     * @param string URL to foward the customer to.
     * @param string Optional human readable reasons for not accepting the transaction.
     */
    public function invalid($nextUrl, $detail = null)
    {
        $this->sendResponse(static::RESPONSE_STATUS_INVALID, $nextUrl, $detail);
    }

    /**
     * Construct the response body.
     *
     * @param string The status to send to Sage Pay, one of static::RESPONSE_STATUS_*
     * @param string URL to forward the customer to.
     * @param string Optional human readable reason for this response.
     */
    public function getResponseBody($status, $nextUrl, $detail = null)
    {
        $body = [
            'Status=' . $status,
            'RedirectUrl=' . $nextUrl,
        ];

        if ($detail !== null) {
            $body[] = 'StatusDetail=' . $detail;
        }

        return implode(static::LINE_SEP, $body);
    }

    /**
     * Respond to SagePay confirming or rejecting the notification.
     *
     * @param string The status to send to Sage Pay, one of static::RESPONSE_STATUS_*
     * @param string URL to forward the customer to.
     * @param string Optional human readable reason for this response.
     */
    public function sendResponse($status, $nextUrl, $detail = null)
    {
        $message = $this->getResponseBody($status, $nextUrl, $detail);

        echo $message;

        if ((bool)$this->getExitOnResponse()) {
            exit;
        }
    }

    /**
     * Overrides the Form/Server/Direct method since there is no
     * getRequest() to inspect in a notification.
     */
    public function getTransactionId()
    {
        return $this->getDataItem('VendorTxCode');
    }
}
