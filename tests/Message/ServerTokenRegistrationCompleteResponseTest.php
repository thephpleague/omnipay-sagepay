<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;
use Mockery as m;

class ServerTokenRegistrationCompleteResponseTest extends TestCase
{
    public function testTokenRegistrationCompleteResponseSuccess()
    {
        $response = new ServerTokenRegistrationCompleteResponse($this->getMockRequest(), array(
            'Status' => 'OK',
        ));

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
    }

    public function testTokenRegistrationCompleteResponseInvalid()
    {
        $response = new ServerTokenRegistrationCompleteResponse($this->getMockRequest(), array(
            'Status' => 'INVALID'
        ));

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
    }

    public function testTokenRegistrationCompleteResponseError()
    {
        $response = new ServerTokenRegistrationCompleteResponse($this->getMockRequest(), array(
            'Status' => 'ERROR'
        ));

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
    }

    public function testConfirm()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerTokenRegistrationCompleteResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('OK', 'https://www.example.com/', 'detail');

        $response->confirm('https://www.example.com/', 'detail');
    }

    public function testError()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerTokenRegistrationCompleteResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('ERROR', 'https://www.example.com/', 'detail');

        $response->error('https://www.example.com/', 'detail');
    }

    public function testInvalid()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerTokenRegistrationCompleteResponse')->makePartial();
        $response->shouldReceive('sendResponse')->once()->with('INVALID', 'https://www.example.com/', 'detail');

        $response->invalid('https://www.example.com/', 'detail');
    }

    public function testSendResponse()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerTokenRegistrationCompleteResponse')->makePartial();
        $response->shouldReceive('exitWith')->once()->with("Status=FOO\r\nRedirectUrl=https://www.example.com/");

        $response->sendResponse('FOO', 'https://www.example.com/');
    }

    public function testSendResponseDetail()
    {
        $response = m::mock('\Omnipay\SagePay\Message\ServerTokenRegistrationCompleteResponse')->makePartial();
        $response->shouldReceive('exitWith')->once()->with("Status=FOO\r\nRedirectUrl=https://www.example.com/\r\nStatusDetail=Bar");

        $response->sendResponse('FOO', 'https://www.example.com/', 'Bar');
    }
}