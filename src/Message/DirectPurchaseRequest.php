<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Direct Purchase Request
 */
class DirectPurchaseRequest extends DirectAuthorizeRequest
{
    protected $action = 'PAYMENT';

    /**
     * A repeat payment is just REPEAT, not REPEATPAYMENT.
     *
     * @return string
     * @author Dom Morgan <dom@d3r.com>
     */
    public function getTxType()
    {
        if ($this->isRepeat()) {
            return 'REPEAT';
        }
        return $this->action;
    }
}
