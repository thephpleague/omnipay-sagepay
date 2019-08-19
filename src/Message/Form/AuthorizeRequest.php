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
     * Get the full set of Sage Pay Form data, most of which is encrypted.
     * TxType is only PAYMENT, DEFERRED or AUTHENTICATE
     *
     * @reurn array
     */
    public function getData()
    {
        $this->validate('currency', 'description', 'encryptionKey', 'returnUrl');

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
     * @return array the data required to be encoded into the form crypt field.
     * @throws InvalidRequestException if any mandatory fields are missing
     */
    public function getCryptData()
    {
        $data = $this->getBaseAuthorizeData();

        // Some [optional] parameters specific to Sage Pay Form..

        if ($this->getCustomerName() !== null) {
            $data['CustomerName'] = $this->getCustomerName();
        }

        if ($this->getVendorEmail() !== null) {
            $data['VendorEMail'] = $this->getVendorEmail();
        }

        if ($this->getEmailMessage() !== null) {
            $data['EmailMessage'] = $this->getEmailMessage();
        }

        if ($this->getAllowGiftAid() !== null) {
            $data['AllowGiftAid'] = (bool)$this->getAllowGiftAid()
                ? static::ALLOW_GIFT_AID_YES : static::ALLOW_GIFT_AID_NO;
        }

        if ($this->getWebsite() !== null) {
            $data['Website'] = $this->getWebsite();
        }

        if ($sendEmail = $this->getSendEmail() !== null) {
            if ($sendEmail != static::SEND_EMAIL_NONE
                && $sendEmail != static::SEND_EMAIL_BOTH
                && $sendEmail != static::SEND_EMAIL_VENDOR
            ) {
                $sendEmail = static::SEND_EMAIL_BOTH;
            }

            $data['SendEMail'] = $this->getSendEmail();
        }

        $data['SuccessURL'] = $this->getReturnUrl();
        $data['FailureURL'] = $this->getFailureUrl() ?: $this->getReturnUrl();

        // Filter out any fields that are not accepted by the form API.
        // Unexpected fields throw a general 5080 error which is very
        // difficult to debug.

        $data = array_intersect_key($data, $this->validFields);

        // Throw exception if any mandatory fields are missing.
        // We need to catch it here before sending the user to a
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
        // Maybe this can be moved to the construction of the addresses
        // in AbstractRequest.

        if ($data['BillingCountry'] === 'US' && empty($data['BillingState'])
            || $data['DeliveryCountry'] === 'US' && empty($data['DeliveryState'])
        ) {
            throw new InvalidRequestException(
                'Missing state code for billing or shipping address'
            );
        }

        return $data;
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

        // The encrypted data MUST be ISO8859-1 regardless of what encoding
        // is used to submit the form, because that is how the gateway treats
        // the data internally.
        // This package assumes input data will be UTF-8 by default, and will
        // comvert it accordingly. This can be disabled if the data is already
        // ISO8859-1.
        // For the Server and Direct gateway methods, the POST encoding type
        // will tell the gateway how to interpret the character encoding, and
        // the gateway will do any encoding conversions necessary.

        // We cannot use http_build_query() because the gateway does
        // not decode the string as any standard encoded query string.
        // We just join the names and values with "=" and "&" and the
        // gateway somehow decodes ambiguous strings.

        $disableUtf8Decode = (bool)$this->getDisableUtf8Decode();

        $query = [];
        foreach ($data as $name => $value) {
            $query[] = $name . '=' . ($disableUtf8Decode ? $value : utf8_decode($value));
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

    /**
     * @param string|null $value Customer's name will be included in the confirmation emails
     * @return $this
     */
    public function setCustomerName($value)
    {
        return $this->setParameter('customerName', $value);
    }

    /**
     * @return string|null
     */
    public function getCustomerName()
    {
        return $this->getParameter('customerName');
    }

    /**
     * @param string|null $value An email will be sent to this address when each transaction completes
     * @return $this
     */
    public function setVendorEmail($value)
    {
        return $this->setParameter('vendorEmail', $value);
    }

    /**
     * @return string|null
     */
    public function getVendorEmail()
    {
        return $this->getParameter('vendorEmail');
    }

    /**
     * @param string|null $value 0, 1, or 2, see constants SEND_EMAIL_*
     * @return $this
     */
    public function setSendEmail($value)
    {
        return $this->setParameter('sendEmail', $value);
    }

    /**
     * @return string|null
     */
    public function getSendEmail()
    {
        return $this->getParameter('sendEmail');
    }

    /**
     * This message can be formatted using HTML, up to 1000 bytes.
     *
     * @param string|null $value A message to the customer, inserted into successful emails
     * @return $this
     */
    public function setEmailMessage($value)
    {
        return $this->setParameter('EmailMessage', $value);
    }

    /**
     * @return string|null
     */
    public function getEmailMessage()
    {
        return $this->getParameter('EmailMessage');
    }

    /**
     * @param string|null $value
     * @return $this
     */
    public function setWebsite($value)
    {
        return $this->setParameter('website', $value);
    }

    /**
     * @return string|null
     */
    public function getWebsite()
    {
        return $this->getParameter('website');
    }
}
