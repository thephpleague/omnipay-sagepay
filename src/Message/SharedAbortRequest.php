<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Shared Abort Request
 */
class SharedAbortRequest extends SharedVoidRequest
{
    public function getTxType()
    {
        return static::TXTYPE_ABORT;
    }
}
