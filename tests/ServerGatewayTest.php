<?php

namespace Omnipay\SagePay;

use Omnipay\Tests\GatewayTestCase;

class ServerGatewayTest extends GatewayTestCase
{
    protected $error_3082_text = '3082 : The Description value is too long.';

    public function setUp()
    {
        parent::setUp();

        $this->gateway = new ServerGateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->setVendor('example');

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

        $this->completePurchaseOptions = array(
            'amount' => '10.00',
            'transactionId' => '123',
            'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
        );

        $this->voidOptions = array(
            'transactionId' => '123',
            'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
        );

        $this->abortOptions = array(
            'transactionId' => '123',
            'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
        );

        $this->refundOptions = array(
            'amount' => '10.00',
            'currency' => 'GBP',
            'transactionId' => '123',
            'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
            'description' => 'Some kind of refund',
        );

        $this->repeatOptions = array(
            'amount' => '10.00',
            'currency' => 'GBP',
            'transactionId' => '123',
            'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
            'description' => 'Some kind of repeat payment',
        );
    }

    public function testInheritsDirectGateway()
    {
        $this->assertInstanceOf('Omnipay\SagePay\DirectGateway', $this->gateway);
    }

    public function testAuthorizeSuccess()
    {
        $this->setMockHttpResponse('ServerPurchaseSuccess.txt');

        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('{"SecurityKey":"IK776BWNHN","VPSTxId":"{1E7D9C70-DBE2-4726-88EA-D369810D801D}","VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('Server transaction registered successfully.', $response->getMessage());
        $this->assertSame('https://test.sagepay.com/Simulator/VSPServerPaymentPage.asp?TransactionID={1E7D9C70-DBE2-4726-88EA-D369810D801D}', $response->getRedirectUrl());
    }

    public function testAuthorizeFailure()
    {
        $this->setMockHttpResponse('ServerPurchaseFailure.txt');

        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame($this->error_3082_text, $response->getMessage());
    }

    public function testCompleteAuthorizeSuccess()
    {
        $this->getHttpRequest()->request->replace(
            array(
                'Status' => 'OK',
                'TxAuthNo' => 'b',
                'AVSCV2' => 'c',
                'AddressResult' => 'd',
                'PostCodeResult' => 'e',
                'CV2Result' => 'f',
                'GiftAid' => 'g',
                '3DSecureStatus' => 'h',
                'CAVV' => 'i',
                'AddressStatus' => 'j',
                'PayerStatus' => 'k',
                'CardType' => 'l',
                'Last4Digits' => 'm',
                // New fields for protocol v3.00
                'DeclineCode' => '00',
                'ExpiryDate' => '0722',
                'BankAuthCode' => '999777',
                'VPSSignature' => md5(
                    '{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}'
                    . '438791' . 'OK' . 'bexamplecJEUPDN1N7Edefghijklm' . '00' . '0722' . '999777'
                ),
            )
        );

        $response = $this->gateway->completeAuthorize($this->completePurchaseOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(
            '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"b","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"123"}',
            $response->getTransactionReference()
        );
        $this->assertNull($response->getMessage());
    }

    /**
     * @expectedException Omnipay\Common\Exception\InvalidResponseException
     */
    public function testCompleteAuthorizeInvalid()
    {
        $response = $this->gateway->completeAuthorize($this->completePurchaseOptions)->send();
    }

    public function testPurchaseSuccess()
    {
        $this->setMockHttpResponse('ServerPurchaseSuccess.txt');

        $response = $this->gateway->purchase($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('{"SecurityKey":"IK776BWNHN","VPSTxId":"{1E7D9C70-DBE2-4726-88EA-D369810D801D}","VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame('Server transaction registered successfully.', $response->getMessage());
        $this->assertSame('https://test.sagepay.com/Simulator/VSPServerPaymentPage.asp?TransactionID={1E7D9C70-DBE2-4726-88EA-D369810D801D}', $response->getRedirectUrl());
    }

    public function testPurchaseFailure()
    {
        $this->setMockHttpResponse('ServerPurchaseFailure.txt');

        $response = $this->gateway->purchase($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertSame($this->error_3082_text, $response->getMessage());
    }

    public function testCompletePurchaseSuccess()
    {
        $this->getHttpRequest()->request->replace(
            array(
                'Status' => 'OK',
                'TxAuthNo' => 'b',
                'AVSCV2' => 'c',
                'AddressResult' => 'd',
                'PostCodeResult' => 'e',
                'CV2Result' => 'f',
                'GiftAid' => 'g',
                '3DSecureStatus' => 'h',
                'CAVV' => 'i',
                'AddressStatus' => 'j',
                'PayerStatus' => 'k',
                'CardType' => 'l',
                'Last4Digits' => 'm',
                'VPSSignature' => md5('{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}438791OKbexamplecJEUPDN1N7Edefghijklm'),
            )
        );

        $response = $this->gateway->completePurchase($this->completePurchaseOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"b","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertNull($response->getMessage());
    }

    /**
     * @expectedException Omnipay\Common\Exception\InvalidResponseException
     */
    public function testCompletePurchaseInvalid()
    {
        $response = $this->gateway->completePurchase($this->completePurchaseOptions)->send();
    }

    // Repeat Authorize

    public function testRepeatAuthorizeSuccess()
    {
        $this->setMockHttpResponse('SharedRepeatAuthorize.txt');

        $response = $this->gateway->repeatAuthorize($this->repeatOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('Successful repeat.', $response->getMessage());
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

    public function testRepeatAuthorizeFailure()
    {
        $this->setMockHttpResponse('SharedRepeatAuthorizeFailure.txt');

        $response = $this->gateway->repeatAuthorize($this->repeatOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Not authorized.', $response->getMessage());
    }

    // Repeat Purchase

    public function testRepeatPurchaseSuccess()
    {
        $this->setMockHttpResponse('SharedRepeatAuthorize.txt');

        $response = $this->gateway->repeatPurchase($this->repeatOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('Successful repeat.', $response->getMessage());
    }

    public function testRepeatPurchaseFailure()
    {
        $this->setMockHttpResponse('SharedRepeatAuthorizeFailure.txt');

        $response = $this->gateway->repeatPurchase($this->repeatOptions)->send();

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
}
