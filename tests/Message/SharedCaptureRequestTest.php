<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class SharedCaptureRequestTest extends TestCase
{
    protected $request;

    public function setUp(): void
    {
        parent::setUp();

        $this->request = new SharedCaptureRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
                'testMode' => true,
            )
        );
    }

    public function testTxType()
    {
        // Default TxType is RELEASE.

        $this->assertSame('RELEASE', $this->request->getTxType());

        // User authenticate explicitly true.

        $this->request->setUseAuthenticate(true);

        $this->assertSame('AUTHORISE', $this->request->getTxType());

        // User authenticate explicitly false (back to the default).

        $this->request->setUseAuthenticate(false);

        $this->assertSame('RELEASE', $this->request->getTxType());
    }

    public function testMissingAmount()
    {
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);

        $this->request->getData();
    }

    public function testValid()
    {
        $this->request->setAmount(123.45);

        $data = $this->request->getData();

        $this->assertSame('123.45', $data['ReleaseAmount']);
        $this->assertSame('438791', $data['VendorTxCode']);

        $this->assertSame('{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}', $data['VPSTxId']);
        $this->assertSame('JEUPDN1N7E', $data['SecurityKey']);
        $this->assertSame('4255', $data['TxAuthNo']);
    }

    public function testAuthMissingDescription()
    {
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);

        $this->request->setAmount(123.45);
        $this->request->setUseAuthenticate(true);

        $this->request->getData();
    }

    public function testAuthMissingTransactionId()
    {
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);

        $this->request->setAmount(123.45);
        $this->request->setDescription('desc');
        $this->request->setUseAuthenticate(true);

        $this->request->getData();
    }

    public function testAuthValid()
    {
        $this->request->setAmount(123.45);
        $this->request->setDescription('desc');
        $this->request->setTransactionId('438791-NEW');
        $this->request->setUseAuthenticate(true);

        $data = $this->request->getData();

        $this->assertSame('123.45', $data['Amount']);
        $this->assertSame('438791-NEW', $data['VendorTxCode']);
        $this->assertSame('desc', $data['Description']);

        $this->assertSame('438791', $data['RelatedVendorTxCode']);
        $this->assertSame('{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}', $data['RelatedVPSTxId']);
        $this->assertSame('JEUPDN1N7E', $data['RelatedSecurityKey']);
        $this->assertSame('4255', $data['RelatedTxAuthNo']);
    }
}
