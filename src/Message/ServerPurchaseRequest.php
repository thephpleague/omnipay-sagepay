<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Server Purchase Request
 */
class ServerPurchaseRequest extends ServerAuthorizeRequest
{

    protected $action = 'PAYMENT';

    public function setSurchargeXml($surchargeXml)
    {
        $this->setParameter('surchargeXml', $surchargeXml);
    }

    public function getSurchargeXml()
    {
        return $this->getParameter('surchargeXml');
    }

    public function getData()
    {
        $data = parent::getData();

        $surchargeXml = $this->getSurchargeXml();
        if ($surchargeXml) {
            $data['surchargeXml'] = $this->getSurchargeXml();
        }

        return $data;
    }
}
