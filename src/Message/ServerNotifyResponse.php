<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Sage Pay Server Notification Respons.
 * Return the appropriate response to Sage Pay.
 */
class ServerNotifyResponse extends Response
{
    /**
     * Valid status responses.
     */
    const RESPONSE_STATUS_OK = 'OK';
    const RESPONSE_STATUS_ERROR = 'ERROR';
    const RESPONSE_STATUS_INVALID = 'INVALID';

    /**
     * Live separator for return message to Sage Pay.
     */
    const LINE_SEP = "\r\n";

    /**
     * Whether to exit immediately on responding.
     */
    protected $exit_on_response = true;

    public function isValid()
    {
        // Get the status from the request, which will work only if not using
        // mocked requests during testing.
        if (method_exists($this->getRequest(), 'isValid')) {
            $valid = $this->getRequest()->isValid();
        } else {
            $valid = !empty($this->data['isValid']);
        }

        return $valid;
    }

    public function getTransactionReference()
    {
        return $this->getRequest()->getTransactionReference();
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
            throw new InvalidResponseException('Attempted to confirm an invalid notification');
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
        // If the signature is invalid, then do not allow the confirm.
        if (! $this->isValid()) {
            throw new InvalidResponseException('Attempted to reject an invalid notification');
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
     * Convenience method.
     */
    public function getData()
    {
        return $this->request->getData();
    }

    /**
     * Convenience method.
     */
    public function getTransactionStatus()
    {
        return $this->request->getTransactionStatus();
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
     * Respond to SagePay confirming or rejecting the notification.
     *
     * @param string The status to send to Sage Pay, one of static::RESPONSE_STATUS_*
     * @param string URL to forward the customer to.
     * @param string Optional human readable reasons for this response.
     */
    public function sendResponse($status, $nextUrl, $detail = null)
    {
        $message = array(
            'Status=' . $status,
            'RedirectUrl=' . $nextUrl,
        );

        if ($detail !== null) {
            $message[] = 'StatusDetail=' . $detail;
        }

        echo implode(static::LINE_SEP, $message);

        if ($this->exit_on_response) {
            exit;
        }
    }
}
