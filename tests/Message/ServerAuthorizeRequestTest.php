<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class ServerAuthorizeRequestTest extends TestCase
{
    const SURCHARGE_XML = '<surcharges><surcharge><paymentType>VISA</paymentType><percentage>2.50</percentage></surcharge></surcharges>';

    public function setUp()
    {
        parent::setUp();

        $this->request = new ServerAuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'amount' => '12.00',
                'transactionId' => '123',
                'surchargeXml' => self::SURCHARGE_XML,
                'card' => $this->getValidCard(),
                'notifyUrl' => 'https://www.example.com/return',
                'profile' => 'LOW',
            )
        );
    }

    public function testProfile()
    {
        $this->assertSame($this->request, $this->request->setProfile('NORMAL'));
        $this->assertSame('NORMAL', $this->request->getProfile());
    }

    public function testGetData()
    {
        $data = $this->request->getData();

        $this->assertSame('https://www.example.com/return', $data['NotificationURL']);
        $this->assertSame('LOW', $data['Profile']);
        $this->assertSame(self::SURCHARGE_XML, $data['surchargeXml']);
    }
}
