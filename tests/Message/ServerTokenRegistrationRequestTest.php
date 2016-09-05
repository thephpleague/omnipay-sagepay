<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;
use Mockery as m;

class ServerTokenRegistrationRequestTest extends TestCase
{
    /**
     * @var ServerTokenRegistrationRequest
     */
    private $request;

    public function setUp()
    {
        parent::setUp();

        $this->request = new ServerTokenRegistrationRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'vendor' => 'the_vendor',
                'transactionId' => '123',
                'returnUrl' => 'http://notifyme.com'
            )
        );
    }

    public function testRequestDataIsCorrect()
    {
        $data = $this->request->getData();

        // Key assertion is that we are passing this TxType parameter in the request
        $this->assertSame('TOKEN', $data['TxType']);

        $this->assertSame('3.00', $data['VPSProtocol']);
        $this->assertSame('the_vendor', $data['Vendor']);
        $this->assertSame('123', $data['VendorTxCode']);
        $this->assertSame('http://notifyme.com', $data['NotificationURL']);
    }
}
