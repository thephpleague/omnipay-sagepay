<?php

namespace Omnipay\SagePay\Extend;

/**
 * Extends the Item class to support properties
 */

use Omnipay\Common\ItemInterface as CommonItemInterface;

interface ItemInterface extends CommonItemInterface
{
    /**
     * Set the item VAT.
     */
    public function setVat($value);

    /**
     * Get the item VAT.
     */
    public function getVat();

    /**
     * Set the item Product Code.
     */
    public function setProductCode($value);

    /**
     * Get the item Product Code.
     */
    public function getProductCode();
}
