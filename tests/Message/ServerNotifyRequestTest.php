<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;
use Mockery as m;

/**
 *
 */

class ServerNotifyRequestTest extends TestCase
{
    public function testServerNotifyResponseSuccess()
    {
        parent::setUp();

        // Mock up the server request with first, as ServerNotifyRequest
        // only grabs the POST data once on instantiation.

        $this->getHttpRequest()->initialize(
            [], // GET
            [
                'VendorTxCode' => '438791',
                'Status' => 'OK',
                'TxAuthNo' => '4255',
                'VPSTxId' => '{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}',
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
                'Last4Digits' => '1234',
                'DeclineCode' => '00',
                'ExpiryDate' => '0722',
                'BankAuthCode' => '999777',
                'VPSSignature' => '285765407193faa9b8e432e6b55f5849',
            ] // POST
        );

        $this->request = new ServerNotifyRequest(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        // The security key will be null until we add it.

        $this->assertSame(
            '{"SecurityKey":null,"TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
            $this->request->getTransactionReference()
        );

        // Until we add the security key we saved, the signature check will
        // be false.

        $this->assertFalse($this->request->isValid());

        $this->request->setSecurityKey('JEUPDN1N7E');

        // With the security key added, the signatue check will now be valid,
        // i.e. an untampered inbound notification.

        $this->assertTrue($this->request->isValid());

        // Now with security key.

        $this->assertSame(
            '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
            $this->request->getTransactionReference()
        );

        $this->assertNull($this->request->getMessage());

        $this->assertSame('0722', $this->request->getExpiryDate());
    }

    public function testServerNotifyRequestFailure()
    {
        $this->getHttpRequest()->initialize(
            [], // GET
            [
                'VendorTxCode' => '438791',
                'Status' => 'INVALID',
            ]
        );

        $this->request = new ServerNotifyRequest(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        // Fix this - the transactino reference in Response and ServerNotifyTrait
        // needs to a) return null if there is no data; and b) be consistent in
        // format and order of fields.

        //$this->assertNull($this->request->getTransactionReference());

        $this->assertSame('failed', $this->request->getTransactionStatus());

        $this->assertNull($this->request->getMessage());
    }

    public function DISABLED_testConfirm()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerNotifyResponse', array('isValid' => 1))->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('OK', 'https://www.example.com/', 'detail');

        $response->confirm('https://www.example.com/', 'detail');
        //$response->sendResponse('OK', 'https://www.example.com/', 'detail');
    }

    public function DISABLED_testError()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerNotifyResponse', array('isValid' => 1))->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('ERROR', 'https://www.example.com/', 'detail');

        $response->error('https://www.example.com/', 'detail');
        //$response->sendResponse('ERROR', 'https://www.example.com/', 'detail');
    }

    public function DISABLED_testInvalid()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerNotifyResponse', array('isValid' => 0))->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('INVALID', 'https://www.example.com/', 'detail');

        $response->invalid('https://www.example.com/', 'detail');
        //$response->sendResponse('INVALID', 'https://www.example.com/', 'detail');
    }

    public function DISABLED_testSendResponse()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse')->makePartial();
        $response->shouldReceive('exitWith')->once()->with("Status=FOO\r\nRedirectUrl=https://www.example.com/");

        $response->sendResponse('FOO', 'https://www.example.com/');
    }

    public function DISABLED_testSendResponseDetail()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse')->makePartial();
        $response->shouldReceive('exitWith')->once()->with("Status=FOO\r\nRedirectUrl=https://www.example.com/\r\nStatusDetail=Bar");

        $response->sendResponse('FOO', 'https://www.example.com/', 'Bar');
    }

    public function DISABLED_testServerNotifyResponseSuccess()
    {
        $VPSTxId = '{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}';

        $transactionReference = '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"'.$VPSTxId.'","VendorTxCode":"438791"}';

        $response = new ServerNotifyResponse(
            $this->getMockRequest(),
            array(
                'Status' => 'OK',
                'TxAuthNo' => '4255',
                'VendorTxCode' => '438791',
                'VPSTxId' => $VPSTxId,
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
                'Last4Digits' => '1234',
                'DeclineCode' => '00',
                'ExpiryDate' => '0722',
                'BankAuthCode' => '999777',
                'VPSSignature' => '54b1939f699b6d71c756b701d96baa06',
                // Parameter values (for calculating the signature).
                'vendor' => 'academe',
                'securityKey' => 'JEUPDN1N7E',
            )
        );

        //$this->getMockRequest()->shouldReceive('getTransactionReference')->once()->andReturn($transactionReference);

        $this->assertSame('OK', $response->getCode());

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame($transactionReference, $response->getTransactionReference());
        $this->assertNull($response->getMessage());

        $this->assertSame('0722', $response->getExpiryDate());
        $this->assertSame('2022-07', $response->getExpiryDate('Y-m'));
        $this->assertSame(7, $response->getExpiryMonth());
        $this->assertSame(2022, $response->getExpiryYear());
        $this->assertSame('1234', $response->getNumberLastFour());
        $this->assertSame('1234', $response->getLast4Digits());

        $this->assertSame('completed', $response->getTransactionStatus());
    }
}
