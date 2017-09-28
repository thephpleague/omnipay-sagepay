<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Sage Pay Server Notification Respons.
 * Return the appropriate response to Sage Pay.
 */
class ServerNotifyResponse extends Response implements \Omnipay\Common\Message\NotificationInterface
{
    use ServerNotifyTrait;

    /**
     * Valid status responses.
     */
    const RESPONSE_STATUS_OK        = 'OK';
    const RESPONSE_STATUS_ERROR     = 'ERROR';
    const RESPONSE_STATUS_INVALID   = 'INVALID';

    /**
     * Live separator for return message to Sage Pay.
     */
    const LINE_SEP = "\r\n";

    /**
     * Whether to exit immediately on responding.
     * For 3.0 it will be worth switching this off by default to
     * provide more control to the application.
     */
    protected $exit_on_response = true;

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
     * The security key was set as a parameter in the server request, but passed
     * to the response as a data item.
     */
    public function getSecurityKey()
    {
        return $this->getDataItem('securityKey');
    }

    /**
     * The vendor was set as a parameter in the server request, but passed
     * to the response as a data item.
     */
    public function getVendor()
    {
        return $this->getDataItem('vendor');
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
        // CHECKME: why?
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
     * Set or reset flag to exit immediately on responding.
     * Switch auto-exit off if you have further processing to do.
     * @param boolean true to exit; false to not exit.
     */
    public function setExitOnResponse($value)
    {
        $this->exit_on_response = (bool)$value;
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
        $body = array(
            'Status=' . $status,
            'RedirectUrl=' . $nextUrl,
        );

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

        if ($this->exit_on_response) {
            exit;
        }
    }
}
