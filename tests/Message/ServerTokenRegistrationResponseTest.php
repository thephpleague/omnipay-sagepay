<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class ServerTokenRegistrationResponseTest extends TestCase
{
    public function setUp()
    {
        $this->getMockRequest()->shouldReceive('getTransactionId')->andReturn('123456');
    }

    public function testTokenRegistrationSuccess()
    {
        $httpResponse = $this->getMockHttpResponse('ServerTokenRegistrationSuccess.txt');
        $response = new ServerTokenRegistrationResponse($this->getMockRequest(), $httpResponse->getBody());

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('{"SecurityKey":"IK776BWNHN","VPSTxId":"{1E7D9C70-DBE2-4726-88EA-D369810D801D}","VendorTxCode":"123456"}', $response->getTransactionReference());
        $this->assertSame('Server transaction registered successfully.', $response->getMessage());
        $this->assertSame('https://test.sagepay.com/Simulator/VSPServerPaymentPage.asp?TransactionID={1E7D9C70-DBE2-4726-88EA-D369810D801D}', $response->getRedirectUrl());
        $this->assertSame('GET', $response->getRedirectMethod());
        $this->assertNull($response->getRedirectData());
    }

    public function testTokenRegistrationFailure()
    {
        $httpResponse = $this->getMockHttpResponse('ServerTokenRegistrationFailure.txt');
        $response = new ServerTokenRegistrationResponse($this->getMockRequest(), $httpResponse->getBody());

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"VendorTxCode":"123456"}', $response->getTransactionReference());
        $this->assertSame('3082 : The Description value is too long.', $response->getMessage());
    }
}
