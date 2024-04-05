<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Message\RequestInterface;
use Omnipay\Tests\TestCase;

class ResponseTest extends TestCase
{
    public function setUp()
    {
        $this->getMockRequest()->shouldReceive('getTransactionId')->andReturn('123456');
    }

    public function testDirectPurchaseSuccess()
    {
        $httpResponse = $this->getMockHttpResponse('DirectPurchaseSuccess.txt');
        $response = new Response(
            $this->getMockRequest(),
            AbstractRequest::parseBodyData($httpResponse)
        );

        $this->assertSame('OK', $response->getCode());

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"SecurityKey":"OUWLNYQTVT","TxAuthNo":"9962","VPSTxId":"{5A1BC414-5409-48DD-9B8B-DCDF096CE0BE}","VendorTxCode":"123456"}', $response->getTransactionReference());
        $this->assertSame('Direct transaction from Simulator.', $response->getMessage());
    }

    public function testDirectPurchaseFailure()
    {
        $httpResponse = $this->getMockHttpResponse('DirectPurchaseFailure.txt');
        $response = new Response(
            $this->getMockRequest(),
            AbstractRequest::parseBodyData($httpResponse)
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertNull($response->getTransactionReference());
        $this->assertSame('The VendorTxCode \'984297\' has been used before.  Each transaction you send should have a unique VendorTxCode.', $response->getMessage());
    }

    public function testDirectPurchase3dSecure()
    {
        $httpResponse = $this->getMockHttpResponse('DirectPurchase3dSecure.txt');
        $response = new Response(
            $this->getMockRequest(),
            AbstractRequest::parseBodyData($httpResponse)
        );

        $this->getMockRequest()->shouldReceive('getReturnUrl')->once()->andReturn('https://www.example.com/return');

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getMessage());
        $this->assertSame('https://sandbox.opayo.eu.elavon.com/Simulator/3DAuthPage.asp', $response->getRedirectUrl());

        $redirectData = $response->getRedirectData();
        $this->assertSame('065379457749061954', $redirectData['MD']);
        $this->assertSame('BSkaFwYFFTYAGyFbAB0LFRYWBwsBZw0EGwECEX9YRGFWc08pJCVVKgAANS0KADoZCCAMBnIeOxcWRg0LERdOOTQRDFRdVHNYUgwTMBsBCxABJw4DJHE+ERgPCi8MVC0HIAROCAAfBUk4ER89DD0IWDkvMQ1VdFwoUFgwXVYvbHgvMkdBXXNbQGIjdl1ZUEc1XSwqAAgUUicYBDYcB3I2AjYjIzsn', $redirectData['PaReq']);
        $this->assertSame('https://www.example.com/return', $redirectData['TermUrl']);
    }

    public function testCaptureSuccess()
    {
        $httpResponse = $this->getMockHttpResponse('SharedCaptureSuccess.txt');
        $response = new Response(
            $this->getMockRequest(),
            AbstractRequest::parseBodyData($httpResponse)
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertNull($response->getTransactionReference());
        $this->assertSame('The transaction was RELEASEed successfully.', $response->getMessage());
    }

    public function testCaptureFailure()
    {
        $httpResponse = $this->getMockHttpResponse('SharedCaptureFailure.txt');
        $response = new Response(
            $this->getMockRequest(),
            AbstractRequest::parseBodyData($httpResponse)
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertNull($response->getTransactionReference());
        $this->assertSame('You are trying to RELEASE a transaction that has already been RELEASEd or ABORTed.', $response->getMessage());
    }

    public function testDirectPurchaseWithToken()
    {
        $httpResponse = $this->getMockHttpResponse('DirectPurchaseWithToken.txt');
        $response = new Response(
            $this->getMockRequest(),
            AbstractRequest::parseBodyData($httpResponse)
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('{ABCDEFGH-ABCD-ABCD-ABCD-ABCDEFGHIJKL}', $response->getToken());
    }


    public function testRedirectMethodIsPost()
    {
        $httpResponse = new Response($this->prophesize(RequestInterface::class)->reveal(), []);
        $this->assertEquals('POST', $httpResponse->getRedirectMethod());
    }

    public function testDataGetters()
    {
        $vPSTxId = (string) rand(0, 100);
        $securityKey = (string) rand(0, 100);
        $data = [
            'VPSTxId' => $vPSTxId,
            'SecurityKey' => $securityKey,
        ];
        $httpResponse = new Response($this->prophesize(RequestInterface::class)->reveal(), $data);

        $this->assertEquals($vPSTxId, $httpResponse->getVPSTxId());
        $this->assertEquals($securityKey, $httpResponse->getSecurityKey());
    }
}
