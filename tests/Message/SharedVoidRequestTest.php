<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class SharedVoidRequestTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->request = new SharedVoidRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
                'testMode' => true,
            )
        );
    }

    public function testGetData()
    {
        $data = $this->request->getData();

        $this->assertSame('VOID', $data['TxType']);

        // Reference to the transaction to void.
        $this->assertSame('438791', $data['VendorTxCode']);
        $this->assertSame('{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}', $data['VPSTxId']);
        $this->assertSame('JEUPDN1N7E', $data['SecurityKey']);
        $this->assertSame('4255', $data['TxAuthNo']);
    }

    public function testGetEndpoint()
    {
        $url = $this->request->getEndpoint();

        $this->assertSame('https://test.sagepay.com/gateway/service/void.vsp', $url);
    }
}
