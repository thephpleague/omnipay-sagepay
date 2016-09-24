<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class SharedTokenRemovalRequestTest extends TestCase
{
    /**
     * @var TokenRemovalRequest
     */
    private $request;

    public function setUp()
    {
        $this->request = new SharedTokenRemovalRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(array(
            'vendor' => 'testvendor',
            'token' => '{ABCDE-ABCD-ABCD-ABCD-ABCDE}'
        ));
    }

    public function testGetData()
    {
        $data = $this->request->getData();

        $this->assertSame('{ABCDE-ABCD-ABCD-ABCD-ABCDE}', $data['Token']);
        $this->assertSame('REMOVETOKEN', $data['TxType']);
        $this->assertSame('3.00', $data['VPSProtocol']);
        $this->assertSame('testvendor', $data['Vendor']);
        $this->assertArrayNotHasKey('AccountType', $data);
    }
}
