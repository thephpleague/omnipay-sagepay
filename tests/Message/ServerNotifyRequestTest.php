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

        // The request can be "sent" and you just get back the same request,
        // with all the same detgails.

        $response = $this->request->send();

        $this->assertSame(
            '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
            $response->getTransactionReference()
        );

        $this->assertNull($response->getMessage());

        $this->assertSame('0722', $response->getExpiryDate());

        $this->assertSame($this->request, $response);

        // Confirm will work if the signature is valid.

        $this->expectOutputString(
            "Status=OK\r\nRedirectUrl=https://www.example.com/\r\nStatusDetail=detail"
        );
        $this->request->confirm('https://www.example.com/', 'detail');

        // Issue https://github.com/thephpleague/omnipay-sagepay/issues/124
        // The isSuccessful() method is moved to the trait that shares many
        // common response fields with the notification server request.

        $this->assertTrue($this->request->isSuccessful());
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

        // The transactino reference in Response and ServerNotifyTrait
        // will return null if there is no transaction data provided
        // by the gateway.

        $this->assertNull($this->request->getTransactionReference());

        $this->assertSame('failed', $this->request->getTransactionStatus());

        $this->assertNull($this->request->getMessage());

        $this->assertFalse($this->request->isSuccessful());
    }

    public function testError()
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

        $this->request->setSecurityKey('JEUPDN1N7E');

        // This transaction is valid and the signature is okay, but we
        // don't want to accept it for some internal reason.

        $this->expectOutputString(
            "Status=ERROR\r\nRedirectUrl=https://www.example.com/\r\nStatusDetail=detail"
        );
        $this->request->error('https://www.example.com/', 'detail');
    }

    /**
     * @expectedException \Omnipay\Common\Exception\InvalidResponseException
     */
    public function testConfirmInvalidSignature()
    {
        $this->request = new ServerNotifyRequest(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        // Since there is no valid signature, trying to confirm should
        // throw an exception.

        $this->request->confirm('https://www.example.com/', 'detail');
    }

    /**
     * @expectedException \Omnipay\Common\Exception\InvalidResponseException
     */
    public function testErrorInvalidSignature()
    {
        $this->request = new ServerNotifyRequest(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        $this->request->error('https://www.example.com/', 'detail');
    }

    public function testInvalid()
    {
        $this->request = new ServerNotifyRequest(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        $this->expectOutputString(
            "Status=INVALID\r\nRedirectUrl=https://www.example.com/\r\nStatusDetail=detail"
        );
        $this->request->invalid('https://www.example.com/', 'detail');
    }

    /**
     * sendRequest lets you return a raw message with no additinal
     * checks on the validity of what was received.
     */
    public function testSendResponse()
    {
        $this->request = new ServerNotifyRequest(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        $this->expectOutputString(
            "Status=FOO\r\nRedirectUrl=https://www.example.com/"
        );
        $this->request->sendResponse('FOO', 'https://www.example.com/');
    }

    public function testSendResponseDetail()
    {
        $this->request = new ServerNotifyRequest(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        $this->expectOutputString(
            "Status=FOO\r\nRedirectUrl=https://www.example.com/\r\nStatusDetail=Bar"
        );
        $this->request->sendResponse('FOO', 'https://www.example.com/', 'Bar');
    }

    /**
     * @dataProvider statusDataProvider
     */
    public function testTransactionStatusMapping($status, $txStatus)
    {
        parent::setUp();

        $postData = [
            'VendorTxCode' => '438791',
            'Status' => $status,
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
            'VPSSignature' => null,
        ];

        // First build a notification request without a signature.

        $this->getHttpRequest()->initialize(
            [], // GET
            $postData
        );

        $this->request = new ServerNotifyRequest(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        $this->request->setSecurityKey('JEUPDN1N7E');

        // With an invalid signature the status will always be 'failed'.

        $this->assertSame(
            'failed',
            $this->request->getTransactionStatus()
        );

        // Calculate what the signature should have been.

        $postData['VPSSignature'] = $this->request->buildSignature();

        // Then rebuild the same notification with the signature this time.

        $this->getHttpRequest()->initialize(
            [], // GET
            $postData
        );

        $this->request = new ServerNotifyRequest(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        $this->request->setSecurityKey('JEUPDN1N7E');

        // Test the result again.

        $this->assertSame(
            $txStatus,
            $this->request->getTransactionStatus()
        );
    }

    public function statusDataProvider()
    {
        return [
            ['OK', 'completed'],
            ['OK REPEATED', 'completed'],
            ['AUTHENTICATED', 'completed'],
            ['REGISTERED', 'completed'],
            //
            ['PENDING', 'pending'],
            //
            ['REJECTED', 'failed'],
            ['ABORT', 'failed'],
            ['ERROR', 'failed'],
        ];
    }
}
