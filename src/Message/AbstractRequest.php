<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Abstract Request.
 * Base for Sage Pay Server and Sage Pay Direct.
 */
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\SagePay\Extend\Item as ExtendItem;
use Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;
use Omnipay\SagePay\Traits\GatewayParamsTrait;
use Omnipay\SagePay\ConstantsInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractRequest extends OmnipayAbstractRequest implements ConstantsInterface
{
    use GatewayParamsTrait;

    /**
     * @var string The service name, used in the endpoint URL.
     */
    protected $service;

    /**
     * @var string The protocol version number.
     */
    protected $VPSProtocol = '3.00';

    /**
     * @var string Endpoint base URLs.
     */
    protected $liveEndpoint = 'https://live.sagepay.com/gateway/service';
    protected $testEndpoint = 'https://test.sagepay.com/gateway/service';

    /**
     * Convenience method to switch iframe mode on or off.
     * This sets the profile parameter.
     *
     * @param bool $value True to use an iframe profile for hosted forms.
     * @return $this
     */
    public function setIframe($value)
    {
        $profile = ((bool)$value ? static::PROFILE_LOW : static::PROFILE_NORMAL);

        return $this->setParameter('profile', $profile);
    }

    /**
     * The name of the service used in the endpoint to send the message.
     * For MANY services, the URL fragment will be the lower case version
     * of the action.
     *
     * @return string Sage Pay endpoint service name.
     */
    public function getService()
    {
        return strtolower($this->getTxType());
    }

    /**
     * If it is used, i.e. needed for an enpoint, then it must be defined.
     *
     * @return string the transaction type.
     * @throws InvalidRequestException
     */
    public function getTxType()
    {
        throw new InvalidRequestException('Transaction type not defined.');
    }

    /**
     * Basic authorisation, transaction type and protocol version.
     *
     * @return Array
     */
    protected function getBaseData()
    {
        $data = array();

        $data['VPSProtocol'] = $this->VPSProtocol;
        $data['TxType'] = $this->getTxType();
        $data['Vendor'] = $this->getVendor();
        $data['AccountType'] = $this->getAccountType() ?: static::ACCOUNT_TYPE_E;

        // TODO: move this to getDerivedLanguage()

        if ($language = $this->getLanguage()) {
            // Although documented as ISO639, the gateway expects
            // the code to be upper case.

            $language = strtoupper($language);

            // If a locale has been passed in instead, then just take the first part.
            // e.g. both "en" and "en-gb" becomes "EN".

            list($language) = preg_split('/[-_]/', $language);

            $data['Language'] = $language;
        }

        return $data;
    }

    /**
     * Get either the billing or the shipping address from
     * the card object, mapped to Sage Pay field names.
     *
     * @param string $type 'Billing' or 'Shipping'
     * @return array
     */
    protected function getAddressData($type = 'Billing')
    {
        $card = $this->getCard();

        // Mapping is Sage Pay name => Omnipay Name

        $mapping = [
            'Firstnames'    => 'FirstName',
            'Surname'       => 'LastName',
            'Address1'      => 'Address1',
            'Address2'      => 'Address2',
            'City'          => 'City',
            'PostCode'      => 'Postcode',
            'State'         => 'State',
            'Country'       => 'Country',
            'Phone'         => 'Phone',
        ];

        $data = [];

        foreach ($mapping as $sagepayName => $omnipayName) {
            $data[$sagepayName] = call_user_func([$card, 'get' . $type . $omnipayName]);
        }

        // The state must not be set for non-US countries.

        if ($data['Country'] !== 'US') {
            $data['State'] = '';
        }

        return $data;
    }

    /**
     * Add the billing address details to the data.
     *
     * @param array $data
     * @return array $data
     */
    protected function getBillingAddressData(array $data = [])
    {
        $address = $this->getAddressData('Billing');

        foreach ($address as $name => $value) {
            $data['Billing' . $name] = $value;
        }

        return $data;
    }

    /**
     * Add the delivery (shipping) address details to the data.
     * Use the Billing address if the billingForShipping option is set.
     *
     * @param array $data
     * @return array $data
     */
    protected function getDeliveryAddressData(array $data = [])
    {
        $address = $this->getAddressData(
            (bool)$this->getBillingForShipping() ? 'Billing' : 'Shipping'
        );

        foreach ($address as $name => $value) {
            $data['Delivery' . $name] = $value;
        }

        return $data;
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
                'POST',
                $this->getEndpoint(),
                [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                http_build_query($data)
            );

        // We might want to check $httpResponse->getStatusCode()

        $responseData = static::parseBodyData($httpResponse);

        return $this->createResponse($responseData);
    }

    /**
     * The payload consists of name=>value pairs, each on a separate line.
     *
     * @param ResponseInterface $httpResponse
     * @return array
     */
    public static function parseBodyData(ResponseInterface $httpResponse)
    {
        $bodyText = (string)$httpResponse->getBody();

        // Split the bodyText into lines.

        $lines = preg_split('/[\n\r]+/', $bodyText);

        $responseData = [];

        foreach ($lines as $line) {
            $line = explode('=', $line, 2);

            if (! empty($line[0])) {
                $responseData[trim($line[0])] = isset($line[1]) ? trim($line[1]) : '';
            }
        }

        return $responseData;
    }

    /**
     * @return string URL for the test or live gateway, as appropriate.
     */
    public function getEndpoint()
    {
        return sprintf(
            '%s/%s.vsp',
            $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint,
            $this->getService()
        );
    }

    /**
     * Indicates whether a NORMAL or LOW profile page is to be used
     * for hosted forms.
     *
     * @return string|null
     */
    public function getProfile()
    {
        return $this->getParameter('profile');
    }

    /**
     * @param string $value One of static::PROFILE_NORMAL or static::PROFILE_LOW
     * @return $this
     */
    public function setProfile($value)
    {
        return $this->setParameter('profile', $value);
    }

    /**
     * @return string The custom vendor data.
     */
    public function getVendorData()
    {
        return $this->getParameter('vendorData');
    }

    /**
     * Set custom vendor data that will be stored against the gateway account.
     *
     * @param string $value ASCII alphanumeric and spaces, max 200 characters.
     */
    public function setVendorData($value)
    {
        return $this->setParameter('vendorData', $value);
    }

    /**
     * Use this flag to indicate you wish to have a token generated and stored in the Sage Pay
     * database and returned to you for future use.
     * Values set in constants CREATE_TOKEN_*
     *
     * @param bool|int $createToken 0 = This will not create a token from the payment (default).
     * @return $this
     */
    public function setCreateToken($value)
    {
        return $this->setParameter('createToken', $value);
    }

    /**
     * @return int static::CREATE_TOKEN_YES or static::CREATE_TOKEN_NO
     */
    public function getCreateToken()
    {
        return $this->getParameter('createToken');
    }

    /**
     * Alias for setCreateToken()
     */
    public function setCreateCard($value)
    {
        return $this->setCreateToken($value);
    }

    /**
     * Alias for getCreateToken()
     */
    public function getCreateCard()
    {
        return $this->getCreateToken();
    }

    /**
     * An optional flag to indicate if you wish to continue to store the
     * Token in the SagePay token database for future use.
     * Values set in contants SET_TOKEN_*
     *
     * Note: this is just an override method. It is best to leave this unset,
     * and use either setToken or setCardReference. This flag will then be
     * set automatically.
     *
     * @param bool|int|null $value Will be cast to bool when used
     * @return $this
     */
    public function setStoreToken($value)
    {
        return $this->setParameter('storeToken', $value);
    }

    /**
     * @return bool|int|null
     */
    public function getStoreToken()
    {
        return $this->getParameter('storeToken');
    }

    /**
     * @param string the original VPS transaction ID; used to capture/void
     * @return $this
     */
    public function setVpsTxId($value)
    {
        return $this->setParameter('vpsTxId', $value);
    }

    /**
     * @return string
     */
    public function getVpsTxId()
    {
        return $this->getParameter('vpsTxId');
    }

    /**
     * @param string the original SecurityKey; used to capture/void
     * @return $this
     */
    public function setSecurityKey($value)
    {
        return $this->setParameter('securityKey', $value);
    }

    /**
     * @return string
     */
    public function getSecurityKey()
    {
        return $this->getParameter('securityKey');
    }

    /**
     * @param string the original txAuthNo; used to capture/void
     * @return $this
     */
    public function setTxAuthNo($value)
    {
        return $this->setParameter('txAuthNo', $value);
    }

    /**
     * @return string
     */
    public function getTxAuthNo()
    {
        return $this->getParameter('txAuthNo');
    }

    /**
     * @param string the original txAuthNo; used to capture/void
     * @return $this
     */
    public function setRelatedTransactionId($value)
    {
        return $this->setParameter('relatedTransactionId', $value);
    }

    /**
     * @return string
     */
    public function getRelatedTransactionId()
    {
        return $this->getParameter('relatedTransactionId');
    }

    /**
     * @return int static::ALLOW_GIFT_AID_YES or static::ALLOW_GIFT_AID_NO
     */
    public function getAllowGiftAid()
    {
        return $this->getParameter('allowGiftAid');
    }

    /**
     * This flag allows the gift aid acceptance box to appear for this transaction
     * on the payment page. This only appears if your vendor account is Gift Aid enabled.
     *
     * Values defined in static::ALLOW_GIFT_AID_* constant.
     *
     * @param bool|int $allowGiftAid value that casts to boolean
     * @return $this
     */
    public function setAllowGiftAid($value)
    {
        $this->setParameter('allowGiftAid', $value);
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
     * Filters out any characters that SagePay does not support from the item name.
     *
     * Believe it or not, SagePay actually have separate rules for allowed characters
     * for item names and discount names, hence the need for two separate methods.
     *
     * @param string $name
     * @return string
     */
    protected function filterItemName($name)
    {
        $standardChars = '0-9a-zA-Z';
        $allowedSpecialChars = " +'/\\&:,.-{}";
        $pattern = '`[^'.$standardChars.preg_quote($allowedSpecialChars, '/').']`';
        $name = trim(substr(preg_replace($pattern, '', $name), 0, 100));

        return $name;
    }

    /**
     * Filters out any characters that SagePay does not support from the item name for
     * the non-xml basket integration
     *
     * @param string $name
     * @return string
     */
    protected function filterNonXmlItemName($name)
    {
        $standardChars = '0-9a-zA-Z';
        $allowedSpecialChars = " +'/\\,.-{};_@()^\"~$=!#?|[]";
        $pattern = '`[^'.$standardChars.preg_quote($allowedSpecialChars, '/').']`';
        $name = trim(substr(preg_replace($pattern, '', $name), 0, 100));

        return $name;
    }

    /**
     * Filters out any characters that SagePay does not support from the discount name.
     *
     * Believe it or not, SagePay actually have separate rules for allowed characters
     * for item names and discount names, hence the need for two separate methods.
     *
     * @param string $name
     * @return string
     */
    protected function filterDiscountName($name)
    {
        $standardChars = "0-9a-zA-Z";
        $allowedSpecialChars = " +'/\\:,.-{};_@()^\"~[]$=!#?|";
        $pattern = '`[^'.$standardChars.preg_quote($allowedSpecialChars, '/').']`';
        $name = trim(substr(preg_replace($pattern, '', $name), 0, 100));

        return $name;
    }

    /**
     * Get an XML representation of the current cart items
     *
     * @return string The XML string; an empty string if no basket items are present
     */
    protected function getItemData()
    {
        $result = '';
        $items = $this->getItems();

        // If there are no items, then do not construct any of the basket.
        if (empty($items) || $items->all() === array()) {
            return $result;
        }

        $xml = new \SimpleXMLElement('<basket/>');
        $cartHasDiscounts = false;

        foreach ($items as $basketItem) {
            if ($basketItem->getPrice() < 0) {
                $cartHasDiscounts = true;
            } else {
                $vat = '0.00';
                if ($basketItem instanceof ExtendItem) {
                    $vat = $basketItem->getVat();
                }

                $total = ($basketItem->getQuantity() * ($basketItem->getPrice() + $vat));
                $item = $xml->addChild('item');
                $item->description = $this->filterItemName($basketItem->getName());
                $item->addChild('quantity', $basketItem->getQuantity());
                $item->addChild('unitNetAmount', $basketItem->getPrice());
                $item->addChild('unitTaxAmount', $vat);
                $item->addChild('unitGrossAmount', $basketItem->getPrice() + $vat);
                $item->addChild('totalGrossAmount', $total);
            }
        }

        if ($cartHasDiscounts) {
            $discounts = $xml->addChild('discounts');
            foreach ($items as $discountItem) {
                if ($discountItem->getPrice() < 0) {
                    $discount = $discounts->addChild('discount');
                    $discount->addChild('fixed', ($discountItem->getPrice() * $discountItem->getQuantity()) * -1);
                    $discount->description = $this->filterDiscountName($discountItem->getName());
                }
            }
        }

        $xmlString = $xml->asXML();

        if ($xmlString) {
            $result = $xmlString;
        }

        return $result;
    }

    /**
     * Generate Basket string in the older non-XML format
     * This is called if "useOldBasketFormat" is set to true in the gateway config
     *
     * @return string Basket field in format of:
     *    1:Item:2:10.00:0.00:10.00:20.00
     *    [number of lines]:[item name]:[quantity]:[unit cost]:[item tax]:[item total]:[line total]
     */
    protected function getItemDataNonXML()
    {
        $result = '';
        $items = $this->getItems();
        $count = 0;

        foreach ($items as $basketItem) {
            $description = $this->filterNonXmlItemName($basketItem->getName());
            $vat = '0.00';

            if ($basketItem instanceof ExtendItem) {
                $vat = $basketItem->getVat();

                /**
                 * Product Code is used for the Product Sage 50 Accounts Software Integration
                 * It allows reconcile the transactions on your account within the financial software
                 * by linking the product record to a specific transaction.
                 * This is not available for BasketXML and only Basket Integration. See docs for more info.
                 */
                if (!is_null($basketItem->getProductCode())) {
                    $description = '[' . $basketItem->getProductCode() . ']' . $description;
                }
            }

            $lineTotal = ($basketItem->getQuantity() * ($basketItem->getPrice() + $vat));

            $result .= ':' . $description .    // Item name
                ':' . $basketItem->getQuantity() . // Quantity
                ':' . number_format($basketItem->getPrice(), 2, '.', '') . // Unit cost (without tax)
                ':' . $vat . // Item tax
                ':' . number_format($basketItem->getPrice() + $vat, 2, '.', '') . // Item total
                // As the getItemData() puts 0.00 into tax, same was done here
                ':' . number_format($lineTotal, 2, '.', '');  // Line total

            $count++;
        }

        // Prepend number of lines to the result string
        $result = $count . $result;

        return $result;
    }

    /**
     * A JSON transactionReference passed in is split into its
     * component parts.
     *
     * @param string $value original transactionReference in JSON format.
     */
    public function setTransactionReference($value)
    {
        $reference = json_decode($value, true);

        if (json_last_error() === 0) {
            if (isset($reference['VendorTxCode'])) {
                $this->setRelatedTransactionId($reference['VendorTxCode']);
            }

            if (isset($reference['VPSTxId'])) {
                $this->setVpsTxId($reference['VPSTxId']);
            }

            if (isset($reference['SecurityKey'])) {
                $this->setSecurityKey($reference['SecurityKey']);
            }

            if (isset($reference['TxAuthNo'])) {
                $this->setTxAuthNo($reference['TxAuthNo']);
            }
        }

        return parent::setTransactionReference($value);
    }
}
