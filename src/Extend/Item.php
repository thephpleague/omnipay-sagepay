<?php

namespace Omnipay\SagePay\Extend;

/**
 * Extends the Item class to support properties
 */

use Omnipay\Common\Item as CommonItem;

class Item extends CommonItem implements ItemInterface
{
   /**
     * {@inheritDoc}
     */
    public function getVat()
    {
        return $this->getParameter('vat');
    }

    /**
     * {@inheritDoc}
     */
    public function setVat($value)
    {
        return $this->setParameter('vat', $value);
    }

    /**
     * Product Code is used for the Product Sage 50 Accounts Software Integration
     * It allows reconcile the transactions on your account within the financial software
     * by linking the product record to a specific transaction.
     * This is not available for BasketXML and only Basket Integration. See docs for more info.
     * {@inheritDoc}
     */
    public function getProductRecord()
    {
        return $this->getParameter('productRecord');
    }

    /**
     * {@inheritDoc}
     */
    public function setProductRecord($value)
    {
        return $this->setParameter('productRecord', $value);
    }
}
