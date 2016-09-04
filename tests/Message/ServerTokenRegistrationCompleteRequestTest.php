<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class ServerTokenRegistrationCompleteRequestTest extends TestCase
{
    /**
     * @var ServerTokenRegistrationCompleteRequest
     */
    private $request;

    private $successDetails = array(
        'VPSTxId' => '{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}',
        'VendorTxCode' => '123456789',
        'SecurityKey' => 'JEUPDN1N7E',
        'Vendor' => 'TestVendor'
    );

    public function setUp()
    {
        $this->request = new ServerTokenRegistrationCompleteRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(array(
            'transactionId' => '123456789',
            'transactionReference' => '{"VendorTxCode": "123456789", "VPSTxId": "{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}", "SecurityKey": "JEUPDN1N7E"}',
            'vendor' => 'TestVendor'
        ));
    }

    public function testCorrectDataUponSuccessfulSagePayRequest()
    {
        $expectedToken = '{ABCDE-ABCDE-ABCDE-ACBCDE}';

        $signature = $this->generateSignature(
            'F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2',
            $this->successDetails['VendorTxCode'],
            'OK',
            $this->successDetails['Vendor'],
            $expectedToken,
            $this->successDetails['SecurityKey']
        );

        $request = $this->getHttpRequest()->request;
        $request->set('Status', 'OK');
        $request->set('Token', $expectedToken);
        $request->set('VPSSignature', $signature);
        $request->set('CardType', 'VISA');
        $request->set('ExpiryDate', '1220');

        $data = $this->request->getData();

        $this->assertSame($expectedToken, $data['Token']);
        $this->assertSame('VISA', $data['CardType']);
        $this->assertSame('1220', $data['ExpiryDate']);
    }

    /**
     * @expectedException \Omnipay\Common\Exception\InvalidResponseException
     * @throws \Omnipay\Common\Exception\InvalidResponseException
     */
    public function testThrowsInvalidResponseUponIncorrectSagePayRequest()
    {
        $signature = 'adfm49cmexkgi2lddlqmtrbv';
        $request = $this->getHttpRequest()->request;
        $request->set('Status', 'OK');
        $request->set('Token', '{ABCDE-ABCDE-ABCDE-ACBCDE}');
        $request->set('VPSSignature', $signature);
        $request->set('CardType', 'VISA');
        $request->set('ExpiryDate', '1220');

        $this->request->getData();
    }

    private function generateSignature($vpsTxId, $vendorTxCode, $status, $vendor, $token, $securityKey)
    {
        return md5($vpsTxId . $vendorTxCode . $status . $vendor . $token . $securityKey);
    }
}
