<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Abstract Request.
 * Base for Sage Pay Server and Sage Pay Direct.
 */
 use Omnipay\Common\Exception\InvalidRequestException;
 use Omnipay\SagePay\Extend\Item as ExtendItem;

abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    /**
     * @var string The transaction type, used in the request body.
     */
    protected $action;

    /**
     * @var string The service name, used in the endpoint URL.
     */
    protected $service;

    /**
     * @var string The protocol version number.
     */
    protected $VPSProtocol = '3.00';

    /**
     * Supported 3D Secure values for Apply3DSecure.
     * 0: APPLY - If 3D-Secure checks are possible and rules allow,
     *      perform the checks and apply the authorisation rules.
     *      (default)
     * 1: FORCE - Force 3D-Secure checks for this transaction if
     *      possible and apply rules for authorisation.
     * 2: NONE - Do not perform 3D-Secure checks for this
     *      transaction and always authorise.
     * 3: AUTH - Force 3D-Secure checks for this transaction if
     *      possible but ALWAYS obtain an auth code, irrespective
     *      of rule base.
     *
     * @var integer
     */
    const APPLY_3DSECURE_APPLY  = 0;
    const APPLY_3DSECURE_FORCE  = 1;
    const APPLY_3DSECURE_NONE   = 2;
    const APPLY_3DSECURE_AUTH   = 3;

    /**
     * Supported AVS/CV2 values.
     *
     * DEFAULT will use the account settings for checks and applying of rules.
     * FORCE_CHECKS will force checks to be made.
     * NO_CHECKS will force no checks to be performed.
     * NO_RULES will force no rules to be applied.
     *
     * @var integer
     */
    const APPLY_AVSCV2_DEFAULT      = 0;
    const APPLY_AVSCV2_FORCE_CHECKS = 1;
    const APPLY_AVSCV2_NO_CHECKS    = 2;
    const APPLY_AVSCV2_NO_RULES     = 3;

    /**
     * Flag whether to store a cardReference or token for multiple use.
     */
    const STORE_TOKEN_YES   = 1;
    const STORE_TOKEN_NO    = 0;

    /**
     * Flag whether to create a cardReference or token for the CC supplied.
     */
    const CREATE_TOKEN_YES   = 1;
    const CREATE_TOKEN_NO    = 0;

    /**
     * Profile for Sage Pay Server hosted forms.
     * - NORMAL for full page forms.
     * - LOW for use in iframes.
     */
    const PROFILE_NORMAL    = 'NORMAL';
    const PROFILE_LOW       = 'LOW';

    /**
     * The values for the AccountType field.
     * E – for ecommerce transactions (default)
     * M – for telephone (MOTO) transactions
     * C – for repeat transactions
     *
     * @var string
     */
    const ACCOUNT_TYPE_E = 'E';
    const ACCOUNT_TYPE_M = 'M';
    const ACCOUNT_TYPE_C = 'C';

    /**
     * @var string Endpoint base URLs.
     */
    protected $liveEndpoint = 'https://live.sagepay.com/gateway/service';
    protected $testEndpoint = 'https://test.sagepay.com/gateway/service';

    /**
     * @return string The vendor name identified the account.
     */
    public function getVendor()
    {
        return $this->getParameter('vendor');
    }

    /**
     * @param string $value The vendor name, as supplied in lower case.
     * @return $this Provides a fluent inetrface.
     */
    public function setVendor($value)
    {
        return $this->setParameter('vendor', $value);
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
     * Convenience method to switch iframe mode on or off.
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
     * The name of the service used in the endpoint to send the message.
     * For most services, the URL fragment will be the lower case version
     * of the action.
     *
     * @return string Sage Oay endpoint service name.
     */
    public function getService()
    {
        return ($this->service ?: strtolower($this->action));
    }

    /**
     * By default, the XML basket format will be used. This flag can be used to
     * switch back to the older terminated-string format basket. Each basket
     * format supports a different range of features, both in the basket itself
     * and in the data collected and processed in the gateway backend.
     *
     * @param bool $value True to switch the old format basket.
     * @return $this
     */
    public function setUseOldBasketFormat($value)
    {
        $value = (bool)$value;

        return $this->setParameter('useOldBasketFormat', $value);
    }

    /**
     * Returns the current basket format by indicating whether the older
     * terminated-string format is being used.
     *
     * @return bool true for old format basket; false for newer XML format basket.
     */
    public function getUseOldBasketFormat()
    {
        return $this->getParameter('useOldBasketFormat');
    }

    /**
     * @return string One of static::ACCOUNT_TYPE_*
     */
    public function getAccountType()
    {
        return $this->getParameter('accountType');
    }

    /**
     * Set account type.
     * Neither 'M' nor 'C' offer the 3D-Secure checks that the "E" customer
     * experience offers.
     *
     * This is ignored for all PAYPAL transactions.
     *
     * @param string $value E: Use the e-commerce merchant account. (default)
     *                      M: Use the mail/telephone order account. (if present)
     *                      C: Use the continuous authority merchant account. (if present)
     * @return $this
     */
    public function setAccountType($value)
    {
        return $this->setParameter('accountType', $value);
    }

    public function getReferrerId()
    {
        return $this->getParameter('referrerId');
    }

    /**
     * Set the referrer ID for PAYMENT, DEFERRED and AUTHENTICATE transactions.
     */
    public function setReferrerId($value)
    {
        return $this->setParameter('referrerId', $value);
    }

    public function getApplyAVSCV2()
    {
        return $this->getParameter('applyAVSCV2');
    }

    /**
     * Set the apply AVSCV2 checks.
     * Values defined in APPLY_AVSCV2_* constant.
     *
     * @param  int $value 0: If AVS/CV2 enabled then check them. If rules apply, use rules. (default)
     *                    1: Force AVS/CV2 checks even if not enabled for the account. If rules apply
     *                       use rules.
     *                    2: Force NO AVS/CV2 checks even if enabled on account.
     *                    3: Force AVS/CV2 checks even if not enabled for account but DON'T apply any
     *                       rules.
     */
    public function setApplyAVSCV2($value)
    {
        return $this->setParameter('applyAVSCV2', $value);
    }

    /**
     * @return string Once of static::APPLY_3DSECURE_*
     */
    public function getApply3DSecure()
    {
        return $this->getParameter('apply3DSecure');
    }

    /**
     * Whether or not to apply 3D secure authentication.
     *
     * This is ignored for PAYPAL, EUROPEAN PAYMENT transactions.
     * Values defined in APPLY_3DSECURE_* constant.
     *
     * @param  int $value 0: If 3D-Secure checks are possible and rules allow, perform the
     *                       checks and apply the authorisation rules. (default)
     *                    1: Force 3D-Secure checks for this transaction if possible and
     *                       apply rules for authorisation.
     *                    2: Do not perform 3D-Secure checks for this transactios and always
     *                       authorise.
     *                    3: Force 3D-Secure checks for this transaction if possible but ALWAYS
     *                       obtain an auth code, irrespective of rule base.
     * @return $this
     */
    public function setApply3DSecure($value)
    {
        return $this->setParameter('apply3DSecure', $value);
    }

    public function getTxType()
    {
        if (isset($this->action)) {
            return $this->action;
        }
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
          ->post($this->getEndpoint(), null, $data)
          ->send();

        // The body is a string.
        $body = $httpResponse->getBody();

        // Split into lines.
        $lines = preg_split('/[\n\r]+/', $body);

        $response_data = array();

        foreach ($lines as $line) {
            $line = explode('=', $line, 2);
            if (!empty($line[0])) {
                $response_data[trim($line[0])] = isset($line[1]) ? trim($line[1]) : '';
            }
        }

        return $this->createResponse($response_data);
    }

    /**
     * @return string URL for the test or live gateway, as appropriate.
     */
    public function getEndpoint()
    {
        $service = $this->getService();

        if ($this->getTestMode()) {
            return $this->testEndpoint."/$service.vsp";
        }

        return $this->liveEndpoint."/$service.vsp";
    }

    /**
     * Use this flag to indicate you wish to have a token generated and stored in the Sage Pay
     * database and returned to you for future use.
     * Values set in contants CREATE_TOKEN_*
     *
     * @param bool|int $createToken 0 = This will not create a token from the payment (default).
     * @return $this
     */
    public function setCreateToken($createToken)
    {
        $createToken = (bool)$createToken;

        return $this->setParameter(
            'createToken',
            ($createToken ? static::CREATE_TOKEN_YES : static::CREATE_TOKEN_NO)
        );
    }

    /**
     * @return int static::CREATE_TOKEN_YES or static::CREATE_TOKEN_NO
     */
    public function getCreateToken()
    {
        return $this->getParameter('createToken');
    }

    /**
     * An optional flag to indicate if you wish to continue to store the Token in the SagePay
     * token database for future use.
     * Values set in contants SET_TOKEN_*
     *
     * Note: this is just an override method. It is best to leave this unset, and
     * use either setToken or setCardReference. This flag will then be set automatically.
     *
     * @param bool|int $storeToken  0 = The Token will be deleted from the SagePay database if
     *                                  authorised by the bank.
     *                              1 = Continue to store the Token in the SagePay database for future use.
     * @return $this
     */
    public function setStoreToken($storeToken)
    {
        $storeToken = (bool)$storeToken;

        $this->setParameter(
            'storeToken',
            ($storeToken ? static::STORE_TOKEN_YES : static::STORE_TOKEN_NO)
        );
    }

    /**
     * @return int static::STORE_TOKEN_YES or static::STORE_TOKEN_NO
     */
    public function getStoreToken()
    {
        return $this->getParameter('storeToken');
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

            // Make sure there aren't any colons in the name
            // Perhaps ":" should be replaced with '-' or other symbol?
            $description = str_replace(':', ' ', $description);
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
}
