<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Shared Abort Request
 */
class SharedAbortRequest extends SharedVoidRequest
{
    protected $action = 'ABORT';
}
