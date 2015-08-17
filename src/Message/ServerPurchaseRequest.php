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
        $this->setParameter('SurchargeXML', $surchargeXml);
    }

    public function getSurchargeXml()
    {
        return $this->getParameter('SurchargeXML');
    }

    public function getData()
    {
        $data = parent::getData();

        $surchargeXml = $this->getSurchargeXml();
        if($surchargeXml)
        {
            $data['SurchargeXML'] = $this->getSurchargeXml();
        }

        return $data;
    }

}