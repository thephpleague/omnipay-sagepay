<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\NotificationInterface;

/**
 * Sage Pay Server Notification.
 * The gateway will send the results of Server transactions here.
 */
class ServerNotifyRequest extends AbstractRequest implements NotificationInterface
{
    use CardResponseFieldsTrait;
    use ServerNotifyTrait;

    /**
     * Copy of the POST data sent in.
     */
    protected $data;

    public function getData()
    {
        // Grab the data from the request if we don't already have it.
        // This would be a good place to convert the encoding if required
        // e.g. ISO-8859-1 to UTF-8.

        if (!isset($this->data)) {
            $this->data = $this->httpRequest->request->all();
        }

        return $this->data;
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
            // We only need the security key here for the signature; all other
            // items from the reference will be in the server request.

            $parts = json_decode($reference, true);

            if (isset($parts['SecurityKey'])) {
                $this->setSecurityKey($parts['SecurityKey']);
            }
        }
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

    /**
     * Get the Sage Pay Responder.
     *
     * @param string $data message body.
     * @return ServerNotifyResponse
     */
    public function sendData($data)
    {
        $data['vendor'] = $this->getVendor();
        $data['securityKey'] = $this->getSecurityKey();

        return $this->response = new ServerNotifyResponse($this, $data);
    }
}
