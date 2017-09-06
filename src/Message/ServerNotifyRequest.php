<?php

namespace Omnipay\SagePay\Message;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\NotificationInterface;
use Guzzle\Http\ClientInterface;

/**
 * Sage Pay Server Notification.
 * The gateway will send the results of Server transactions here.
 */
class ServerNotifyRequest extends AbstractRequest implements NotificationInterface
{
    use ResponseFieldsTrait;
    use ServerNotifyTrait;

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
}
