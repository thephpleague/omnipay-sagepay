<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Abstract Rest Request.
 * Base for Sage Pay Rest Server.
 */
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\SagePay\Extend\Item as ExtendItem;
use Omnipay\SagePay\ConstantsInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractRestRequest extends AbstractRequest implements ConstantsInterface
{


    /**
     * @var string The service name, used in the endpoint URL.
     */
    protected $service;

    /**
     * @var string The protocol version number.
     */
    protected $apiVersion = 'v1';

    protected $method = 'POST';

    /**
     * @var string Endpoint base URLs.
     */
    protected $liveEndpoint = 'https://pi-live.sagepay.com/api';
    protected $testEndpoint = 'https://pi-test.sagepay.com/api';

    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string URL for the test or live gateway, as appropriate.
     */
    public function getEndpoint()
    {
        return sprintf(
            '%s/%s/%s',
            $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint,
            $this->getApiVersion(),
            $this->isSubservice() ? $this->getSubService() : $this->getService()
        );
    }

    public function isSubservice()
    {
        return !empty($this->getParentService());
    }

    /**
     * The name of the service used in the endpoint to send the message.
     * With override for services used on specific parent services
     *
     * @return string Sage Pay endpoint service name.
     */
    public function getSubService()
    {
        if ($this->isSubservice()) {
            return sprintf(
                '%s/%s/%s',
                $this->getParentService(),
                $this->getParentServiceReference(),
                $this->getService()
            );
        }
        return $this->getService();
    }

    public function getParentService()
    {
        return false;
    }

    public function getParentServiceReference()
    {
        return false;
    }

    /**
     * Gets the api version for the end point.
     *
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    
    public function getUsername()
    {
        return $this->getParameter('username');
    }

    public function setUsername($value)
    {
        return $this->setParameter('username', $value);
    }

    public function getPassword()
    {
        return $this->getParameter('password');
    }

    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }

    public function getMerchantSessionKey()
    {
        return $this->getParameter('merchantSessionKey');
    }

    public function getCardIdentifier()
    {
        return $this->getParameter('cardIdentifier');
    }

    public function setMerchantSessionKey($value)
    {
        return $this->setParameter('merchantSessionKey', $value);
    }

    public function setCardIdentifier($value)
    {
        return $this->setParameter('cardIdentifier', $value);
    }

    /**
     * @return string
     */
    public function getMd()
    {
        return $this->getParameter('MD');
    }

    /**
     * Override the MD passed into the current request.
     *
     * @param string $value
     * @return $this
     */
    public function setMd($value)
    {
        return $this->setParameter('MD', $value);
    }

    /**
     * Send data to the remote gateway, parse the result into an array,
     * then use that to instantiate the response object.
     *
     * @param  array
     * @return Response The reponse object initialised with the data returned from the gateway.
     */
    public function sendData($data)
    {
        // Issue #20 no data values should be null.

        array_walk($data, function (&$value) {
            if (! isset($value)) {
                $value = '';
            }
        });

        $httpResponse = $this
            ->httpClient
            ->request(
                $this->getMethod(),
                $this->getEndpoint(),
                [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic '.base64_encode($this->getUsername() . ':' . $this->getPassword()),
                ],
                json_encode($data)
            );

        // We might want to check $httpResponse->getStatusCode()

        $responseData = static::parseBodyData($httpResponse);

        return $this->createResponse($responseData);
    }

    /**
     * Add the billing address details to the data.
     *
     * @param array $data
     * @return array $data
     */
    protected function getBillingAddressData(array $data = [])
    {
        $card = $this->getCard();

        $data['customerFirstName'] = $card->getFirstName();
        $data['customerLastName'] = $card->getLastName();
        $data['billingAddress']['address1'] = $card->getBillingAddress1();
        $data['billingAddress']['address2'] = $card->getBillingAddress2();
        $data['billingAddress']['city'] = $card->getBillingCity();
        $data['billingAddress']['postalCode'] = $card->getBillingPostcode();
        $data['billingAddress']['state'] = $card->getBillingState();
        $data['billingAddress']['country'] = $card->getBillingCountry();

        if ($data['billingAddress']['country'] !== 'US') {
            $data['billingAddress']['state'] = '';
        }

        return $data;
    }

    /**
     * Add the shipping details to the data.
     *
     * @param array $data
     * @return array $data
     */
    protected function getShippingDetailsData(array $data = [])
    {
        $card = $this->getCard();

        $data['shippingDetails']['recipientFirstName'] = $card->getShippingFirstName();
        $data['shippingDetails']['recipientLastName'] = $card->getShippingLastName();
        $data['shippingDetails']['shippingAddress1'] = $card->getShippingAddress1();
        $data['shippingDetails']['shippingAddress2'] = $card->getShippingAddress2();
        $data['shippingDetails']['shippingCity'] = $card->getShippingCity();
        $data['shippingDetails']['shippingPostalCode'] = $card->getShippingPostcode();
        $data['shippingDetails']['shippingState'] = $card->getShippingState();
        $data['shippingDetails']['shippingCountry'] = $card->getShippingCountry();

        if ($data['shippingDetails']['shippingCountry'] !== 'US') {
            $data['shippingDetails']['shippingState'] = '';
        }

        return $data;
    }

    /**
     * The payload consists of json.
     *
     * @param ResponseInterface $httpResponse
     * @return array
     */
    public static function parseBodyData(ResponseInterface $httpResponse)
    {
        $bodyText = (string)$httpResponse->getBody();

        $responseData = json_decode($bodyText, true);

        return $responseData;
    }
}
