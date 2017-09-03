<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\NotificationInterface;

/**
 * Data access methods shared between the ServerNotificationRequest and
 * the general Response classes. These are mainly the simplified details
 * of user-supplied credit cards.
 */

trait CardResponseFieldsTrait
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
