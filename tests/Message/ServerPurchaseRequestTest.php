<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class ServerPurchaseRequestTest extends TestCase
{

    const SURCHARGE_XML = '<surcharges><surcharge><paymentType>VISA</paymentType><percentage>2.50</percentage></surcharge></surcharges>';

    public function testInitialize()
    {
        $this->request = new ServerPurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'returnUrl' => 'http://www.example.com/return',
                'amount' => '12.00',
                'transactionId' => '123',
                'surchargeXml' => self::SURCHARGE_XML,
                'card' => $this->getValidCard(),
            )
        );

        $data = $this->request->getData();

        $this->assertSame('PAYMENT', $data['TxType']);
        $this->assertSame('vspserver-register', $this->request->getService());
    }

    public function testSetSurchargeXml()
    {
        $request = new ServerPurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize(
            array(
                'returnUrl' => 'https://www.example.com/return',
                'amount' => '12.00',
                'transactionId' => '123',
                'card' => $this->getValidCard(),
            )
        );

        $request->setSurchargeXml(self::SURCHARGE_XML);

        $data = $request->getData();
        $this->assertSame(self::SURCHARGE_XML, $data['surchargeXml']);
    }

}