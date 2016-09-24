<?php

namespace Omnipay\SagePay;

use Omnipay\Tests\GatewayTestCase;

class DirectGatewayTest extends GatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new DirectGateway($this->getHttpClient(), $this->getHttpRequest());

        $this->purchaseOptions = array(
            'amount' => '10.00',
            'transactionId' => '123',
            'card' => $this->getValidCard(),
            'returnUrl' => 'https://www.example.com/return',
        );

        $this->captureOptions = array(
            'amount' => '10.00',
            'transactionId' => '123',
            'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
        );

        $this->repeatOptions = array(
            'amount' => '10.00',
            'currency' => 'GBP',
            'transactionId' => '123',
            'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
            'description' => 'Some kind of repeat payment',
        );

        $this->refundOptions = array(
            'amount' => '10.00',
            'currency' => 'GBP',
            'transactionId' => '123',
            'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
            'description' => 'Some kind of refund',
        );

        $this->voidOptions = array(
            'transactionId' => '123',
            'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
        );

        $this->abortOptions = array(
            'transactionId' => '123',
            'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
        );
    }

    public function testAuthorizeFailureSuccess()
    {
        $this->setMockHttpResponse('DirectPurchaseSuccess.txt');

        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"SecurityKey":"OUWLNYQTVT","TxAuthNo":"9962","VPSTxId":"{5A1BC414-5409-48DD-9B8B-DCDF096CE0BE}","VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('Direct transaction from Simulator.', $response->getMessage());
    }

    public function testAuthorizeFailure()
    {
        $this->setMockHttpResponse('DirectPurchaseFailure.txt');

        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('The VendorTxCode \'984297\' has been used before.  Each transaction you send should have a unique VendorTxCode.', $response->getMessage());
    }

    public function testAuthorize3dSecure()
    {
        $this->setMockHttpResponse('DirectPurchase3dSecure.txt');

        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertNull($response->getMessage());
        $this->assertSame('https://test.sagepay.com/Simulator/3DAuthPage.asp', $response->getRedirectUrl());

        $redirectData = $response->getRedirectData();
        $this->assertSame('065379457749061954', $redirectData['MD']);
        $this->assertSame('BSkaFwYFFTYAGyFbAB0LFRYWBwsBZw0EGwECEX9YRGFWc08pJCVVKgAANS0KADoZCCAMBnIeOxcWRg0LERdOOTQRDFRdVHNYUgwTMBsBCxABJw4DJHE+ERgPCi8MVC0HIAROCAAfBUk4ER89DD0IWDkvMQ1VdFwoUFgwXVYvbHgvMkdBXXNbQGIjdl1ZUEc1XSwqAAgUUicYBDYcB3I2AjYjIzsn', $redirectData['PaReq']);
        $this->assertSame('https://www.example.com/return', $redirectData['TermUrl']);
    }

    public function testPurchaseSuccess()
    {
        $this->setMockHttpResponse('DirectPurchaseSuccess.txt');

        $response = $this->gateway->purchase($this->purchaseOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"SecurityKey":"OUWLNYQTVT","TxAuthNo":"9962","VPSTxId":"{5A1BC414-5409-48DD-9B8B-DCDF096CE0BE}","VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('Direct transaction from Simulator.', $response->getMessage());
    }

    public function testPurchaseFailure()
    {
        $this->setMockHttpResponse('DirectPurchaseFailure.txt');

        $response = $this->gateway->purchase($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('The VendorTxCode \'984297\' has been used before.  Each transaction you send should have a unique VendorTxCode.', $response->getMessage());
    }

    public function testPurchase3dSecure()
    {
        $this->setMockHttpResponse('DirectPurchase3dSecure.txt');

        $response = $this->gateway->purchase($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertNull($response->getMessage());
        $this->assertSame('https://test.sagepay.com/Simulator/3DAuthPage.asp', $response->getRedirectUrl());

        $redirectData = $response->getRedirectData();
        $this->assertSame('065379457749061954', $redirectData['MD']);
        $this->assertSame('BSkaFwYFFTYAGyFbAB0LFRYWBwsBZw0EGwECEX9YRGFWc08pJCVVKgAANS0KADoZCCAMBnIeOxcWRg0LERdOOTQRDFRdVHNYUgwTMBsBCxABJw4DJHE+ERgPCi8MVC0HIAROCAAfBUk4ER89DD0IWDkvMQ1VdFwoUFgwXVYvbHgvMkdBXXNbQGIjdl1ZUEc1XSwqAAgUUicYBDYcB3I2AjYjIzsn', $redirectData['PaReq']);
        $this->assertSame('https://www.example.com/return', $redirectData['TermUrl']);
    }

    // Capture

    public function testCaptureSuccess()
    {
        $this->setMockHttpResponse('SharedCaptureSuccess.txt');

        $response = $this->gateway->capture($this->captureOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('The transaction was RELEASEed successfully.', $response->getMessage());
    }

    public function testCaptureFailure()
    {
        $this->setMockHttpResponse('SharedCaptureFailure.txt');

        $response = $this->gateway->capture($this->captureOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('You are trying to RELEASE a transaction that has already been RELEASEd or ABORTed.', $response->getMessage());
    }

    // Refund

    public function testRefundSuccess()
    {
        $this->setMockHttpResponse('SharedRefundSuccess.txt');

        $response = $this->gateway->refund($this->refundOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('{"SecurityKey":"CLSMJYURGO","TxAuthNo":"9962","VPSTxId":"{5A1BC414-5409-48DD-9B8B-DCDF096CE0BE}","VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('0000 : The Authorisation was Successful.', $response->getMessage());
    }

    public function testRefundFailure()
    {
        $this->setMockHttpResponse('SharedRefundFailure.txt');

        $response = $this->gateway->refund($this->refundOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('{"VPSTxId":"{869BC439-1904-DF8A-DDC6-D13E3599FB98}","VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('3013 : The Description is missing.', $response->getMessage());
    }

    // Repeat Authorize

    public function testRepeatAuthorizeSuccess()
    {
        $this->setMockHttpResponse('SharedRepeatAuthorize.txt');

        $response = $this->gateway->repeatAuthorize($this->captureOptions)->send(); // FIXME: repeat

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('Successful repeat.', $response->getMessage());
    }

    public function testRepeatAuthorizeFailure()
    {
        $this->setMockHttpResponse('SharedRepeatAuthorizeFailure.txt');

        $response = $this->gateway->repeatAuthorize($this->captureOptions)->send(); // FIXME: repeat

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Not authorized.', $response->getMessage());
    }

    // Repeat Purchase

    public function testRepeatPurchaseSuccess()
    {
        $this->setMockHttpResponse('SharedRepeatAuthorize.txt');

        $response = $this->gateway->repeatPurchase($this->captureOptions)->send(); // FIXME: "capture"

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('Successful repeat.', $response->getMessage());
    }

    public function testRepeatPurchaseFailure()
    {
        $this->setMockHttpResponse('SharedRepeatAuthorizeFailure.txt');

        $response = $this->gateway->repeatPurchase($this->captureOptions)->send(); // FIXME: "capture"

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Not authorized.', $response->getMessage());
    }

    // Void

    public function testVoidSuccess()
    {
        $this->setMockHttpResponse('SharedVoidSuccess.txt');

        $response = $this->gateway->void($this->voidOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('2005 : The Void was Successful.', $response->getMessage());
    }

    public function testVoidFailure()
    {
        $this->setMockHttpResponse('SharedVoidFailure.txt');

        $response = $this->gateway->void($this->voidOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('4041 : The Transaction type does not support the requested operation.', $response->getMessage());
    }

    // Abort

    public function testAbortSuccess()
    {
        $this->setMockHttpResponse('SharedAbortSuccess.txt');

        $response = $this->gateway->abort($this->abortOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('2005 : The Abort was Successful.', $response->getMessage());
    }

    public function testAbortFailure()
    {
        $this->setMockHttpResponse('SharedAbortFailure.txt');

        $response = $this->gateway->abort($this->abortOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('4041 : The Transaction type does not support the requested operation.', $response->getMessage());
    }
}
