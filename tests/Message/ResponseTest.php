<?php

namespace Omnipay\SagePay\Message;

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
        $response = new Response($this->getMockRequest(), $httpResponse->getBody());

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"AVSCV2":"ALL MATCH","AddressResult":"MATCHED","CV2Result":"MATCHED","PostCodeResult":"MATCHED","SecurityKey":"OUWLNYQTVT","TxAuthNo":"9962","VPSTxId":"{5A1BC414-5409-48DD-9B8B-DCDF096CE0BE}","VendorTxCode":"123456"}', $response->getTransactionReference());
        $this->assertSame('Direct transaction from Simulator.', $response->getMessage());
    }

    public function testDirectPurchaseFailure()
    {
        $httpResponse = $this->getMockHttpResponse('DirectPurchaseFailure.txt');
        $response = new Response($this->getMockRequest(), $httpResponse->getBody());

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"VendorTxCode":"123456"}', $response->getTransactionReference());
        $this->assertSame('The VendorTxCode \'984297\' has been used before.  Each transaction you send should have a unique VendorTxCode.', $response->getMessage());
    }

    public function testDirectPurchase3dSecure()
    {
        $httpResponse = $this->getMockHttpResponse('DirectPurchase3dSecure.txt');
        $response = new Response($this->getMockRequest(), $httpResponse->getBody());

        $this->getMockRequest()->shouldReceive('getReturnUrl')->once()->andReturn('https://www.example.com/return');

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('{"3DSecureStatus":"OK","VendorTxCode":"123456"}', $response->getTransactionReference());
        $this->assertNull($response->getMessage());
        $this->assertSame('https://test.sagepay.com/Simulator/3DAuthPage.asp', $response->getRedirectUrl());

        $redirectData = $response->getRedirectData();
        $this->assertSame('065379457749061954', $redirectData['MD']);
        $this->assertSame('BSkaFwYFFTYAGyFbAB0LFRYWBwsBZw0EGwECEX9YRGFWc08pJCVVKgAANS0KADoZCCAMBnIeOxcWRg0LERdOOTQRDFRdVHNYUgwTMBsBCxABJw4DJHE+ERgPCi8MVC0HIAROCAAfBUk4ER89DD0IWDkvMQ1VdFwoUFgwXVYvbHgvMkdBXXNbQGIjdl1ZUEc1XSwqAAgUUicYBDYcB3I2AjYjIzsn', $redirectData['PaReq']);
        $this->assertSame('https://www.example.com/return', $redirectData['TermUrl']);
    }

    public function testCaptureSuccess()
    {
        $httpResponse = $this->getMockHttpResponse('SharedCaptureSuccess.txt');
        $response = new Response($this->getMockRequest(), $httpResponse->getBody());

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('{"VendorTxCode":"123456"}', $response->getTransactionReference());
        $this->assertSame('The transaction was RELEASEed successfully.', $response->getMessage());
    }

    public function testCaptureFailure()
    {
        $httpResponse = $this->getMockHttpResponse('SharedCaptureFailure.txt');
        $response = new Response($this->getMockRequest(), $httpResponse->getBody());

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('{"VendorTxCode":"123456"}', $response->getTransactionReference());
        $this->assertSame('You are trying to RELEASE a transaction that has already been RELEASEd or ABORTed.', $response->getMessage());
    }

    public function testDirectPurchaseWithToken()
    {
        $httpResponse = $this->getMockHttpResponse('DirectPurchaseWithToken.txt');
        $response = new Response($this->getMockRequest(), $httpResponse->getBody());

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('{ABCDEFGH-ABCD-ABCD-ABCD-ABCDEFGHIJKL}', $response->getToken());
    }
}
