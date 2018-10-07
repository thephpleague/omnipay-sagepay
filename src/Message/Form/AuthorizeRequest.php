<?php

namespace Omnipay\SagePay\Message\Form;

/**
 * Sage Pay Form Authorize Request.
 */

use Omnipay\SagePay\Message\DirectAuthorizeRequest;
use Omnipay\Common\Exception\InvalidRequestException;

class AuthorizeRequest extends DirectAuthorizeRequest
{
    /**
     * Fields accepted by the Form API.
     * "true" fields are mandatory, "false" fields are optional.
     * The DeliveryState is conditionally mandatory.
     */
    protected $validFields = [
        'VendorTxCode' => true,
        'Amount' => true,
        'Currency' => true,
        'Description' => true,
        'SuccessURL' => true,
        'FailureURL' => true,
        'CustomerName' => false,
        'CustomerEMail' => false,
        'VendorEMail' => false,
        'SendEMail' => false,
        'EmailMessage' => false,
        'BillingSurname' => true,
        'BillingFirstnames' => true,
        'BillingAddress1' => true,
        'BillingAddress2' => false,
        'BillingCity' => true,
        'BillingPostCode' => true,
        'BillingCountry' => true,
        'BillingState' => false,
        'BillingPhone' => false,
        'DeliverySurname' => true,
        'DeliveryFirstnames' => true,
        'DeliveryAddress1' => true,
        'DeliveryAddress2' => false,
        'DeliveryCity' => true,
        'DeliveryPostCode' => true,
        'DeliveryCountry' => true,
        'DeliveryState' => false,
        'DeliveryPhone' => false,
        'Basket' => false,
        'AllowGiftAid' => false,
        'ApplyAVSCV2' => false,
        'Apply3DSecure' => false,
        'BillingAgreement' => false,
        'BasketXML' => false,
        'CustomerXML' => false,
        'SurchargeXML' => false,
        'VendorData' => false,
        'ReferrerID' => false,
        'Language' => false,
        'Website' => false,
        'FIRecipientAcctNumber' => false,
        'FIRecipientSurname' => false,
        'FIRecipientPostcode' => false,
        'FIRecipientDoB' => false,
    ];

    /**
     * @return array the data required to be encoded into the form crypt field.
     */
    public function getCryptData()
    {
        $data = $this->getBaseAuthorizeData();

        // CHECKME: are tokens supported?

        if ($this->getToken() || $this->getCardReference()) {
            // If using a token, then set that data.
            $data = $this->getTokenData($data);
        }

        // TODO: lots more fields in here.

        // TxType: only PAYMENT, DEFERRED or AUTHENTICATE

        $data['SuccessURL'] = $this->getReturnUrl();
        $data['FailureURL'] = $this->getFailureUrl() ?: $this->getReturnUrl();

        // Filter out any fields that are not accepted by the form API.
        // Unexpected fields throw a general 5080 error which is very
        // difficult to debug.

        $data = array_intersect_key($data, $this->validFields);

        // Throw exception if any mandatory fields are missing.
        // We need to catch it here before sending the user to an
        // generic (and useless) error on the gateway site.

        foreach ($this->validFields as $fieldName => $mandatoryFlag) {
            if ($mandatoryFlag && ! isset($data[$fieldName])) {
                throw new InvalidRequestException(sprintf(
                    'The %s parameter is required',
                    $fieldName
                ));
            }
        }

        // Two conditional checks on the "state" fields.
        // We don't check if it is a valid two-character state code.

        if ($data['BillingCountry'] === 'US' && empty ($data['BillingState'])
            || $data['DeliveryCountry'] === 'US' && empty ($data['DeliveryState'])
        ) {
            throw new InvalidRequestException(
                'Missing state code for billing or shipping address'
            );
        }

        return $data;
    }

    /**
     * Add the Form-specific details to the base data.
     * @reurn array
     */
    public function getData()
    {
        $this->validate('currency', 'description');

        // The test mode is included to determine the redirect URL.

        return [
            'VPSProtocol' => $this->VPSProtocol,
            'TxType' => $this->getTxType(),
            'Vendor' => $this->getVendor(),
            'Crypt' => $this->generateCrypt($this->getCryptData()),
            'TestMode' => $this->getTestMode(),
        ];
    }

    /**
     * Generate the crypt field from the source data.
     * @param array $data the name/value pairs to be encrypted
     * @return string encrypted data
     */
    public function generateCrypt(array $data)
    {
        // No data values should be null.

        array_walk($data, function (&$value) {
            if (! isset($value)) {
                $value = '';
            }
        });

        // Build the data in a query string.

        // CHECKME: what happens with UTF-8 data? Do we need to convert
        // any special characters not in the correct ranges?
        // What about options for URL encoding of other characters?

        // We cannot use http_build_query() because the gateway does
        // not decode the string as any standard encoded query string.
        // We just join the names and values with "=" and "&" and the
        // gateway somehow decodes ambiguous strings.

        $query = [];
        foreach ($data as $name => $value) {
            $query[] = $name . '=' . $value;
        }
        $query = implode('&', $query);

        // Encrypted using AES(block size 128-bit) in CBC mode with PKCS#5 padding.

        // AES encryption, CBC blocking with PKCS5 padding then HEX encoding.

        $key = $this->getEncryptionKey();

        // Normally IV (paramert 5, initialization vector) would be a kind of salt.
        // That is more relevant when encrypting user details where multiple users
        // could have identical passwords. But this is a one-off transport of a message
        // that will always be unique, so no variable IV is needed.

        $crypt = openssl_encrypt($query, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $key);

        return '@' . strtoupper(bin2hex($crypt));
    }

    public function sendData($data)
    {
        // The result is always going to be a POST redirect.

        return $this->createResponse($data);
    }

    /**
     * Return the Response object, initialised with the parsed response data.
     * @param  array $data The data parsed from the response gateway body.
     * @return Response
     */
    protected function createResponse($data)
    {
        return $this->response = new Response($this, $data);
    }

    /**
     * @param string|null $value The URL the Form gateway will return to on cancel or error.
     * @return $this
     */
    public function setFailureUrl($value)
    {
        return $this->setParameter('failureUrl', $value);
    }

    /**
     * @return string|null The URL the Form gateway will return to on cancel or error.
     */
    public function getFailureUrl()
    {
        return $this->getParameter('failureUrl');
    }
}
