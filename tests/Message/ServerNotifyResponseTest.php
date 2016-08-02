<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;
use Mockery as m;

class ServerNotifyResponseTest extends TestCase
{
    public function testServerNotifyResponseSuccess()
    {
        $response = new ServerNotifyResponse(
            $this->getMockRequest(),
            array(
                'Status' => 'OK',
                'TxAuthNo' => '4255',
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
                'DeclineCode' => '00',
                'ExpiryDate' => '0722',
                'BankAuthCode' => '999777',
            )
        );

        $this->getMockRequest()->shouldReceive('getTransactionId')->once()->andReturn('438791');
        $this->getMockRequest()->shouldReceive('getTransactionReference')->once()->andReturn('{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}');

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}', $response->getTransactionReference());
        $this->assertNull($response->getMessage());
    }

    public function DISABLED_testServerNotifyResponseFailure()
    {
        $response = new ServerNotifyResponse($this->getMockRequest(), array('Status' => 'INVALID'));

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());

        // The mock request does not have this method (the response gets the
        // reference from the *incoming* serevr request). We really want to mock
        // a notification request.
        //$this->assertNull($response->getTransactionReference());

        $this->assertNull($response->getMessage());
    }

    public function DISABLED_testConfirm()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerNotifyResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('OK', 'https://www.example.com/a', 'detail');

        // Same as above - confirm checks 'isValid' which checks the request, and that method
        // isn't mocked.
        //$response->confirm('https://www.example.com/a', 'detail');
    }

    public function DISABLED_testError()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerNotifyResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('ERROR', 'https://www.example.com/b', 'detail');

        // Same as above - confirm checks 'isValid' which checks the request, and that method
        // isn't mocked.
        //$response->error('https://www.example.com/b', 'detail');
    }

    public function DISABLED_testInvalid()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerNotifyResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('INVALID', 'https://www.example.com/c', 'detail');

        $response->invalid('https://www.example.com/c', 'detail');
    }

    public function DISABLED_testSendResponse()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerNotifyResponse')->makePartial();
        $response->shouldReceive('exitWith')->once()->with("Status=FOO\r\nRedirectUrl=https://www.example.com/d");

        // Can't get this working.
        //$response->sendResponse('FOO', 'https://www.example.com/d');
    }

    public function DISABLED_testSendResponseDetail()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerNotifyResponse')->makePartial();
        $response->shouldReceive('exitWith')->once()->with("Status=FOO\r\nRedirectUrl=https://www.example.com/e\r\nStatusDetail=Bar");

        // Can't get this working.
        //$response->sendResponse('FOO', 'https://www.example.com/e', 'Bar');
    }
}
