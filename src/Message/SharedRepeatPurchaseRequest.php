<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Helper;

/**
 * Sage Pay Direct Repeat Authorize Request
 */
class SharedRepeatPurchaseRequest extends SharedRepeatAuthorizeRequest
{
    protected $action = 'REPEAT';
}
