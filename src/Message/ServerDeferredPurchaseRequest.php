<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Server Deferred payment Request
 */
class ServerDeferredPurchaseRequest extends ServerPurchaseRequest
{
    protected $action = 'DEFERRED';
}
