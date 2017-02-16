<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;
use Mockery as m;

class ServerCompleteAuthorizeResponseTest extends TestCase
{
    public function testServerCompleteAuthorizeResponseSuccess()
    {
        $response = new ServerCompleteAuthorizeResponse(
            $this->getMockRequest(),
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
                'DeclineCode' => '00',
                'ExpiryDate' => '0722',
                'BankAuthCode' => '999777',
            )
        );

        $this->getMockRequest()->shouldReceive('getTransactionId')->once()->andReturn('123');
        $this->getMockRequest()->shouldReceive('getTransactionReference')->once()->andReturn('{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}');

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"b","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"123"}', $response->getTransactionReference());
        $this->assertNull($response->getMessage());
    }

    public function testServerCompleteAuthorizeResponseFailure()
    {
        $response = new ServerCompleteAuthorizeresponse($this->getMockRequest(), array('Status' => 'INVALID'));

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getMessage());
    }

    public function testConfirm()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('OK', 'https://www.example.com/', 'detail', false);

        $response->confirm('https://www.example.com/', 'detail');
    }

    public function testConfirmWithReturn()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('OK', 'https://www.example.com/', 'detail', true);

        $response->confirm('https://www.example.com/', 'detail', true);
    }

    public function testError()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('ERROR', 'https://www.example.com/', 'detail', false);

        $response->error('https://www.example.com/', 'detail');
    }

    public function testErrorWithReturn()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('ERROR', 'https://www.example.com/', 'detail', true);

        $response->error('https://www.example.com/', 'detail', true);
    }

    public function testInvalid()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('INVALID', 'https://www.example.com/', 'detail', false);

        $response->invalid('https://www.example.com/', 'detail');
    }

    public function testInvalidWithReturn()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('INVALID', 'https://www.example.com/', 'detail', true);

        $response->invalid('https://www.example.com/', 'detail', true);
    }

    public function testSendResponse()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse')->makePartial();
        $response->shouldReceive('exitWith')->once()->with("Status=FOO\r\nRedirectUrl=https://www.example.com/");

        $response->sendResponse('FOO', 'https://www.example.com/');
    }

    public function testSendResponseDetail()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse')->makePartial();
        $response->shouldReceive('exitWith')->once()->with("Status=FOO\r\nRedirectUrl=https://www.example.com/\r\nStatusDetail=Bar");

        $response->sendResponse('FOO', 'https://www.example.com/', 'Bar');
    }

    public function testSendResponseDetailWithReturn()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse')->makePartial();

        $responseString = $response->sendResponse('FOO', 'https://www.example.com/', 'Bar', true);

        $this->assertEquals("Status=FOO\r\nRedirectUrl=https://www.example.com/\r\nStatusDetail=Bar", $responseString);
    }
}
