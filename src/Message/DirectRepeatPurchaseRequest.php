<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Helper;

/**
 * Sage Pay Direct Repeat Authorize Request
 */
class DirectRepeatPurchaseRequest extends DirectRepeatAuthorizeRequest
{
    protected $action = 'REPEAT';
}
