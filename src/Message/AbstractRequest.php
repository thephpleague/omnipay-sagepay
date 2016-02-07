<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidRequestException;

/**
 * Sage Pay Abstract Request
 */
abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    protected $liveEndpoint = 'https://live.sagepay.com/gateway/service';
    protected $testEndpoint = 'https://test.sagepay.com/gateway/service';

    public function getVendor()
    {
        return $this->getParameter('vendor');
    }

    public function setVendor($value)
    {
        return $this->setParameter('vendor', $value);
    }

    public function getService()
    {
        return $this->action;
    }

    public function getAccountType()
    {
        return $this->getParameter('accountType');
    }

    /**
     * Set account type.
     * 
     * This is ignored for all PAYPAL transactions.
     * 
     * @param string $value E: Use the e-commerce merchant account. (default)
     *                      M: Use the mail/telephone order account. (if present)
     *                      C: Use the continuous authority merchant account. (if present)
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

    public function getApply3DSecure()
    {
        return $this->getParameter('apply3DSecure');
    }

    /**
     * Whether or not to apply 3D secure authentication.
     * 
     * This is ignored for PAYPAL, EUROPEAN PAYMENT transactions.
     * 
     * @param  int $value 0: If 3D-Secure checks are possible and rules allow, perform the
     *                       checks and apply the authorisation rules. (default)
     *                    1: Force 3D-Secure checks for this transaction if possible and
     *                       apply rules for authorisation.
     *                    2: Do not perform 3D-Secure checks for this transactios and always
     *                       authorise.
     *                    3: Force 3D-Secure checks for this transaction if possible but ALWAYS
     *                       obtain an auth code, irrespective of rule base.
     */
    public function setApply3DSecure($value)
    {
        return $this->setParameter('apply3DSecure', $value);
    }

    protected function getBaseData()
    {
        $data = array();
        $data['VPSProtocol'] = '3.00';
        $data['TxType'] = $this->action;
        $data['Vendor'] = $this->getVendor();
        $data['AccountType'] = $this->getAccountType() ?: 'E';

        return $data;
    }

    public function sendData($data)
    {
        // Issue #20 no data values should be null.
        array_walk($data, function (&$value) {
            if (!isset($value)) {
                $value = '';
            }
        });

        $httpResponse = $this->httpClient->post($this->getEndpoint(), null, $data)->send();

        return $this->createResponse($httpResponse->getBody());
    }

    public function getEndpoint()
    {
        $service = strtolower($this->getService());

        if ($this->getTestMode()) {
            return $this->testEndpoint."/$service.vsp";
        }

        return $this->liveEndpoint."/$service.vsp";
    }

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
     *
     * @return string
     */
    protected function filterItemName($name)
    {
        $standardChars = "0-9a-zA-Z";
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
     *
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
                $total = ($basketItem->getQuantity() * $basketItem->getPrice());
                $item = $xml->addChild('item');
                $item->description = $this->filterItemName($basketItem->getName());
                $item->addChild('quantity', $basketItem->getQuantity());
                $item->addChild('unitNetAmount', $basketItem->getPrice());
                $item->addChild('unitTaxAmount', '0.00');
                $item->addChild('unitGrossAmount', $basketItem->getPrice());
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
}
