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
}
