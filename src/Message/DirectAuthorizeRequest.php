<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Direct Authorize Request
 */
class DirectAuthorizeRequest extends AbstractRequest
{
    protected $action = 'DEFERRED';
    protected $cardBrandMap = array(
        'mastercard' => 'mc',
        'diners_club' => 'dc'
    );

    protected function getBaseAuthorizeData()
    {
        $this->validate('amount', 'card', 'transactionId');
        $card = $this->getCard();

        $data = $this->getBaseData();
        $data['Description'] = $this->getDescription();
        $data['Amount'] = $this->getAmount();
        $data['Currency'] = $this->getCurrency();
        $data['VendorTxCode'] = $this->getTransactionId();
        $data['ClientIPAddress'] = $this->getClientIp();

        if ($this->getReferrerId()) {
            $data['ReferrerID'] = $this->getReferrerId();
        }

        // billing details
        $data['BillingFirstnames'] = $card->getBillingFirstName();
        $data['BillingSurname'] = $card->getBillingLastName();
        $data['BillingAddress1'] = $card->getBillingAddress1();
        $data['BillingAddress2'] = $card->getBillingAddress2();
        $data['BillingCity'] = $card->getBillingCity();
        $data['BillingPostCode'] = $card->getBillingPostcode();
        $data['BillingState'] = $card->getBillingCountry() === 'US' ? $card->getBillingState() : '';
        $data['BillingCountry'] = $card->getBillingCountry();
        $data['BillingPhone'] = $card->getBillingPhone();

        // shipping details
        $data['DeliveryFirstnames'] = $card->getShippingFirstName();
        $data['DeliverySurname'] = $card->getShippingLastName();
        $data['DeliveryAddress1'] = $card->getShippingAddress1();
        $data['DeliveryAddress2'] = $card->getShippingAddress2();
        $data['DeliveryCity'] = $card->getShippingCity();
        $data['DeliveryPostCode'] = $card->getShippingPostcode();
        $data['DeliveryState'] = $card->getShippingCountry() === 'US' ? $card->getShippingState() : '';
        $data['DeliveryCountry'] = $card->getShippingCountry();
        $data['DeliveryPhone'] = $card->getShippingPhone();
        $data['CustomerEMail'] = $card->getEmail();

        if ($this->getUseOldBasketFormat()) {
            $basket = $this->getItemDataNonXML();
            if (!empty($basket)) {
                $data['Basket'] = $basket;
            }
        } else {
            $basketXML = $this->getItemData();
            if (!empty($basketXML)) {
                $data['BasketXML'] = $basketXML;
            }
        }

        return $data;
    }

    public function getData()
    {
        $data = $this->getBaseAuthorizeData();

        if ($this->isRepeat()) {
            $reference = json_decode($this->getTransactionReference(), true);
            $data['RelatedVendorTxCode'] = $reference['VendorTxCode'];
            $data['RelatedVPSTxId'] = $reference['VPSTxId'];
            $data['RelatedSecurityKey'] = $reference['SecurityKey'];
            $data['RelatedTxAuthNo'] = $reference['TxAuthNo'];
        } else {
            $this->getCard()->validate();
            $data['Apply3DSecure'] = $this->getApply3DSecure() ?: 0;
            $data['ApplyAVSCV2'] = $this->getApplyAVSCV2() ?: 0;
            $data['CardHolder'] = $this->getCard()->getName();
            $data['CardNumber'] = $this->getCard()->getNumber();
            $data['CV2'] = $this->getCard()->getCvv();
            $data['ExpiryDate'] = $this->getCard()->getExpiryDate('my');
            $data['CardType'] = $this->getCardBrand();

            if ($this->getCard()->getStartMonth() and $this->getCard()->getStartYear()) {
                $data['StartDate'] = $this->getCard()->getStartDate('my');
            }

            if ($this->getCard()->getIssueNumber()) {
                $data['IssueNumber'] = $this->getCard()->getIssueNumber();
            }
        }

        return $data;
    }

    /**
     * If we are making a repeat transaction, we need the repeat endpoint
     *
     * @return string
     * @author Dom Morgan <dom@d3r.com>
     */
    public function getService()
    {
        if ($this->isRepeat()) {
            return 'repeat';
        }
        return 'vspdirect-register';
    }

    /**
     * We need to send a different type when we repeat a payment
     *
     * @return string
     * @author Dom Morgan <dom@d3r.com>
     */
    public function getTxType()
    {
        if ($this->isRepeat()) {
            return 'REPEAT' . $this->action;
        }
        return $this->action;
    }

    protected function getCardBrand()
    {
        $brand = $this->getCard()->getBrand();

        if (isset($this->cardBrandMap[$brand])) {
            return $this->cardBrandMap[$brand];
        }

        return $brand;
    }

    /**
     * Is this a repeat transaction?
     *
     * @return boolean
     * @author Dom Morgan <dom@d3r.com>
     */
    protected function isRepeat()
    {
        return false != $this->getTransactionReference();
    }
}
