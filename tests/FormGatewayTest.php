<?php

namespace Omnipay\SagePay;

use Omnipay\Tests\GatewayTestCase;

class FormGatewayTest extends GatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new FormGateway(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );
        $this->gateway->setVendor('example');

        $this->purchaseOptions = [
            'amount' => '10.00',
            'currency' => 'EUR',
            'transactionId' => '123',
            'card' => $this->getValidCard(),
            'returnUrl' => 'https://www.example.com/return',
            'encryptionKey' => '12345678abcdeabc',
            'description' => 'Some message',
        ];

        $this->captureOptions = [
            'amount' => '10.00',
            'transactionId' => '123',
            'transactionReference' => '{"SecurityKey":"JEUPDN1N7E","TxAuthNo":"4255","VPSTxId":"{F955C22E-F67B-4DA3-8EA3-6DAC68FA59D2}","VendorTxCode":"438791"}',
        ];

        $this->completePurchaseOptions = [
            'encryptionKey' => '2f52208a25a1facf',
            'transactionId' => 'phpne-demo-53922585',
        ];
    }

    public function testInheritsDirectGateway()
    {
        $this->assertInstanceOf(\Omnipay\SagePay\FormGateway::class, $this->gateway);
    }

    public function testAuthorizeSuccess()
    {
        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());

        $this->assertSame(
            [
                'VPSProtocol' => '3.00',
                'TxType' => 'DEFERRED',
                'Vendor' => 'example',
                'Crypt' => '@BE1508740B97BEC235E9E8474168E3D4DEAD71C1395CDAC1A24F4784A71135AE797F5451F48EB2EAF5669EB3F6953F466FC3A03E5815E607C4DD18C03D7BDD61D176DB4DF131DC0F02DFC5145FFBA841651641BDAA405E72F4EF8849C2B2FD1F08763E3E66EAF3C479429A92014C7B8316F3B446BA09D28A821EE81C243E3DDD6F4C1F41D6ADEAC74D42B221645ADCC69E2F22ECDCAA010F63CCD02EAF0CE20F98439DAC7C31E528A7574656191150C1CC6CCD9FBEFCBFC9B34029FAB8F62DBC1C1618628FF0529B4E66B8AB857A79CC2FD0F14299A3505D22F964322755E6190EDD5BD42066D88154F950585236B6A2951D28BDA474E3FB17638DAA2F6304EAEA3AB9513DD0604D447000208D55DA4FDF544BEE00B5744170C1FC1E6DC6AADA07BEF6EB1FA46C14B99C3371491816A5C2CF7E03EDF6D58142767F7550DFFFE634FA56532605768FFEC1B20BEC32177816DDAB804149E9A301E495F11568F58896E90B1AC5776C2F12CE578955292F640A1E81213AAAA7856CD622FC241C65AC08215B94F933FD47050E0CFB2BFA85D7570A9D401B2366001AA377C50825B6D3893BDEC46D87F9121EC39DBF948F69399B8E842635556C7BC08E2E0C85CFA3EA2F9E4582ABDB3EB9668C83EDC34A5862F3CBC1A10935A189493D42D5C3FE4AF2BFF29B8464934B221B5A56B407F4E638B6766E6C706996A0252F2DDB30AFC8C1DA65F89987F4A9AA317BB104ED42F749DE3A43C39C515B67B3EA619D4092843DA551EEFC22CF672F53F753DEB93F1E8487BFE89C0100AFB9799238B24E523B0D6D53F824DEC0F6897F97FA507648D13276841B3B2E98030CBCD8E04F5E2C06C57BCC6F6C346440E620718F8877FFE969',
                'TestMode' => false,
            ],
            $response->getData()
        );

        // Redirect data is just four of the fields in the full data set.

        $this->assertSame(
            array_intersect_key(
                $response->getData(),
                array_flip(['VPSProtocol', 'TxType', 'Vendor', 'Crypt'])
            ),
            $response->getRedirectData()
        );

        // Live (non-test) endpoint.
        $this->assertSame(
            'https://live.sagepay.com/gateway/service/vspform-register.vsp',
            $response->getRedirectUrl()
        );

        $this->assertSame(
            'POST',
            $response->getRedirectMethod()
        );
    }

    public function testPurchaseSuccess()
    {
        $response = $this->gateway->purchase($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());

        $this->assertSame(
            [
                'VPSProtocol' => '3.00',
                'TxType' => 'PAYMENT',
                'Vendor' => 'example',
                'Crypt' => '@BE1508740B97BEC235E9E8474168E3D4DEAD71C1395CDAC1A24F4784A71135AE797F5451F48EB2EAF5669EB3F6953F466FC3A03E5815E607C4DD18C03D7BDD61D176DB4DF131DC0F02DFC5145FFBA841651641BDAA405E72F4EF8849C2B2FD1F08763E3E66EAF3C479429A92014C7B8316F3B446BA09D28A821EE81C243E3DDD6F4C1F41D6ADEAC74D42B221645ADCC69E2F22ECDCAA010F63CCD02EAF0CE20F98439DAC7C31E528A7574656191150C1CC6CCD9FBEFCBFC9B34029FAB8F62DBC1C1618628FF0529B4E66B8AB857A79CC2FD0F14299A3505D22F964322755E6190EDD5BD42066D88154F950585236B6A2951D28BDA474E3FB17638DAA2F6304EAEA3AB9513DD0604D447000208D55DA4FDF544BEE00B5744170C1FC1E6DC6AADA07BEF6EB1FA46C14B99C3371491816A5C2CF7E03EDF6D58142767F7550DFFFE634FA56532605768FFEC1B20BEC32177816DDAB804149E9A301E495F11568F58896E90B1AC5776C2F12CE578955292F640A1E81213AAAA7856CD622FC241C65AC08215B94F933FD47050E0CFB2BFA85D7570A9D401B2366001AA377C50825B6D3893BDEC46D87F9121EC39DBF948F69399B8E842635556C7BC08E2E0C85CFA3EA2F9E4582ABDB3EB9668C83EDC34A5862F3CBC1A10935A189493D42D5C3FE4AF2BFF29B8464934B221B5A56B407F4E638B6766E6C706996A0252F2DDB30AFC8C1DA65F89987F4A9AA317BB104ED42F749DE3A43C39C515B67B3EA619D4092843DA551EEFC22CF672F53F753DEB93F1E8487BFE89C0100AFB9799238B24E523B0D6D53F824DEC0F6897F97FA507648D13276841B3B2E98030CBCD8E04F5E2C06C57BCC6F6C346440E620718F8877FFE969',
                'TestMode' => false,
            ],
            $response->getData()
        );
    }

    /**
     * The only real failures are validation exceptions in the data
     * supplied, which largely amount to missing parameters.
     *
     * @dataProvider missingParameterProvider
     * @expectedException \Omnipay\Common\Exception\InvalidRequestException
     */
    public function testAuthorizeFailure($missingParameter)
    {
        $parameters = $this->purchaseOptions;

        unset($parameters[$missingParameter]);

        $response = $this->gateway->authorize($parameters)->send();
    }

    public function missingParameterProvider()
    {
        return [
            ['transactionId'],
            ['currency'],
            ['description'],
            ['encryptionKey'],
        ];
    }

    public function testCompleteAuthorizeSuccess()
    {
        $crypt = '@5548276239c33e937e4d9d847d0a01f451d692827902c2b0243c371aff9ed7626571de70f86e4d0f11d64b35243794f04405742cc7dc3a559c3b4b94c7b39af3ff0068c41a575d6fe42243e8cf5ba019fdb2f0584c71fb54052c37af483556698e2f96ec4202706f4ccd351c185bfa310970dd6173b726fa01aeafcd2d2df87a656f5f3f28e528e7ab1f64472a978bda1ec7e883e22ce292570ab5599799a823ce45bb3f105330662987da4029a1e24c9f656390a76acf4075a74e4d16b369c663d4baad5a26ecde3c54ab6c90b26a58008b4da1b3665b954e059fb37705834e55f83d6dcf642b13dd6456034d3d78ad307c29eaeb5c1ce8c0f8c5a976d98adcdc763e20c1ea21036b59e7d0cbde381f9a6298e72172677c3b14a4dd1bece0bb575c78dbdb0e0b5d534bf401b6788025a5024f630c1bba0748dacea45a0d22227434ac2bb69b6dd978784ea3835feb2b70e7f9b950186910583aab1444ca4e8eba9ad9769828e0978b5bfa643920175e488b18c9f7ea4d3ff654386bcd8aa19f2873246d5ef445d879c3ed3fa1459d1b';

        // The complete page will receive a sungle "crypt" query parameter.

        $this->getHttpRequest()->query->replace(
            [
                'crypt' => $crypt,
            ]
        );

        $request = $this->gateway->completeAuthorize($this->completePurchaseOptions);

        $response = $request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(
            '{"TxAuthNo":"19000362","VPSTxId":"{F5A91F56-F9C7-EFAD-8050-3226BBC6F4A8}","VendorTxCode":"phpne-demo-53922585"}',
            $response->getTransactionReference()
        );
        $this->assertSame(
            '0000 : The Authorisation was Successful.',
            $response->getMessage()
        );

        $this->assertSame(
            [
                'VendorTxCode' => 'phpne-demo-53922585',
                'VPSTxId' => '{F5A91F56-F9C7-EFAD-8050-3226BBC6F4A8}',
                'Status' => 'OK',
                'StatusDetail' => '0000 : The Authorisation was Successful.',
                'TxAuthNo' => '19000362',
                'AVSCV2' => 'SECURITY CODE MATCH ONLY',
                'AddressResult' => 'NOTMATCHED',
                'PostCodeResult' => 'NOTMATCHED',
                'CV2Result' => 'MATCHED',
                'GiftAid' => '0',
                '3DSecureStatus' => 'NOTCHECKED',
                'CardType' => 'VISA',
                'Last4Digits' => '0006',
                'DeclineCode' => '00',
                'ExpiryDate' => '1218',
                'Amount' => '99.99',
                'BankAuthCode' => '999777',
            ],
            $response->getdata()
        );
    }

    /**
     * Invalid without any query parameter supplied.
     * @expectedException Omnipay\Common\Exception\InvalidResponseException
     */
    public function testCompleteAuthorizeInvalid()
    {
        $response = $this->gateway->completeAuthorize($this->completePurchaseOptions)->send();
    }

    /**
     * Invalid without any query parameter supplied.
     * @expectedException Omnipay\Common\Exception\InvalidResponseException
     */
    public function testCompletePurchaseInvalid1()
    {
        $response = $this->gateway->completePurchase($this->completePurchaseOptions)->send();
    }

    /**
     * @expectedException Omnipay\Common\Exception\InvalidResponseException
     */
    public function testCompletePurchaseInvalid2()
    {
        // No leading '@'.
        $this->getHttpRequest()->initialize(['crypt' => 'ababab']);
        $response = $this->gateway->completePurchase($this->completePurchaseOptions)->send();
    }

    /**
     * @expectedException Omnipay\Common\Exception\InvalidResponseException
     */
    public function testCompletePurchaseInvalid3()
    {
        // Not hexadecimal.
        $this->getHttpRequest()->initialize(['crypt' => '@ababxx']);
        $response = $this->gateway->completePurchase($this->completePurchaseOptions)->send();
    }

    /**
     * A valid crypt response format, but not decyptable, so empty data.
     */
    public function testCompletePurchaseInvalid4()
    {
        $this->getHttpRequest()->initialize(['crypt' => '@ababab']);
        $response = $this->gateway->completePurchase($this->completePurchaseOptions)->send();

        $this->assertSame([], $response->getData());
    }

    /**
     * This is on return from the gateway with a valid encrypted result.
     */
    public function testCompletePurchaseSuccess()
    {
        // Set the "crypt" query parameter.

        $this->getHttpRequest()->initialize(['crypt' => '@5548276239c33e937e4d9d847d0a01f4c05f1b71dd5cd32568b6985a6d6834aca672315cf3eec01bb20d34cd1ccd7bdd69a9cd89047f7f875103b46efd8f7b97847eea6b6bab5eb8b61da9130a75fffa1c9152b7d39f77e534ea870281b8e280ea1fdbd49a8f5a7c67d1f512fe7a030e81ae6bd2beed762ad074edcd5d7eb4456a6797911ec78e4d16e7d3ac96b919052a764b7ee4940fd6976346608ad8fed1eb6b0b14d84d802c594b3fd94378a26837df66b328f01cfd144f2e7bc166370bf7a833862173412d2798e8739ee7ef9b0568afab0fc69f66af19864480bf3e74fe2fd2043ec90396e40ab62dc9c1f32dee0e309af7561d2286380ebb497105bde2860d401ccfb4cfcd7047ad32e9408d37f5d0fe9a67bd964d5b138b2546a7d54647467c59384eaa20728cf240c460e36db68afdcf0291135f9d5ff58563f14856fd28534a5478ba2579234b247d0d5862c5742495a2ae18c5ca0d6461d641c5215b07e690280fa3eaf5d392e1d6e2791b181a500964d4bc6c76310e47468ae72edddc3c04d83363514c908624747118']);

        $options = $this->completePurchaseOptions;

        // Switch to the transaction ID actually encrypted in the server request.
        $options['transactionId'] = 'phpne-demo-56260425';

        $response = $this->gateway->completePurchase($options)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('{"TxAuthNo":"19052426","VPSTxId":"{B7792365-F7F9-6E20-ACD1-390C5CEBDDAF}","VendorTxCode":"phpne-demo-56260425"}', $response->getTransactionReference());
        $this->assertSame('0000 : The Authorisation was Successful.', $response->getMessage());
        $this->assertSame('19052426', $response->getTxAuthNo());

        $this->assertSame(
            [
                'VendorTxCode' => 'phpne-demo-56260425',
                'VPSTxId' => '{B7792365-F7F9-6E20-ACD1-390C5CEBDDAF}',
                'Status' => 'OK',
                'StatusDetail' => '0000 : The Authorisation was Successful.',
                'TxAuthNo' => '19052426',
                'AVSCV2' => 'SECURITY CODE MATCH ONLY',
                'AddressResult' => 'NOTMATCHED',
                'PostCodeResult' => 'NOTMATCHED',
                'CV2Result' => 'MATCHED',
                'GiftAid' => '0',
                '3DSecureStatus' => 'NOTCHECKED',
                'CardType' => 'VISA',
                'Last4Digits' => '0006',
                'DeclineCode' => '00',
                'ExpiryDate' => '1218',
                'Amount' => '99.99',
                'BankAuthCode' => '999777',
            ],
            $response->getData()
        );
    }

    /**
     * The wrong transaction ID is supplied with the server request.
     *
     * @expectedException Omnipay\Common\Exception\InvalidResponseException
     */
    public function testCompletePurchaseReplayAttack()
    {
        //$this->expectException(Complicated::class);

        // Set the "crypt" query parameter.

        $this->getHttpRequest()->initialize(['crypt' => '@5548276239c33e937e4d9d847d0a01f4c05f1b71dd5cd32568b6985a6d6834aca672315cf3eec01bb20d34cd1ccd7bdd69a9cd89047f7f875103b46efd8f7b97847eea6b6bab5eb8b61da9130a75fffa1c9152b7d39f77e534ea870281b8e280ea1fdbd49a8f5a7c67d1f512fe7a030e81ae6bd2beed762ad074edcd5d7eb4456a6797911ec78e4d16e7d3ac96b919052a764b7ee4940fd6976346608ad8fed1eb6b0b14d84d802c594b3fd94378a26837df66b328f01cfd144f2e7bc166370bf7a833862173412d2798e8739ee7ef9b0568afab0fc69f66af19864480bf3e74fe2fd2043ec90396e40ab62dc9c1f32dee0e309af7561d2286380ebb497105bde2860d401ccfb4cfcd7047ad32e9408d37f5d0fe9a67bd964d5b138b2546a7d54647467c59384eaa20728cf240c460e36db68afdcf0291135f9d5ff58563f14856fd28534a5478ba2579234b247d0d5862c5742495a2ae18c5ca0d6461d641c5215b07e690280fa3eaf5d392e1d6e2791b181a500964d4bc6c76310e47468ae72edddc3c04d83363514c908624747118']);

        // These options contain a different transactionId from the once expected.

        $options = $this->completePurchaseOptions;

        $response = $this->gateway->completePurchase($options)->send();
    }

    /**
     * The missing expected transaction ID supplied by the app.
     *
     * @expectedException Omnipay\Common\Exception\InvalidRequestException
     */
    public function testCompletePurchaseMissingExpectedParam()
    {
        //$this->expectException(Complicated::class);

        // Set the "crypt" query parameter.

        $this->getHttpRequest()->initialize(['crypt' => '@5548276239c33e937e4d9d847d0a01f4c05f1b71dd5cd32568b6985a6d6834aca672315cf3eec01bb20d34cd1ccd7bdd69a9cd89047f7f875103b46efd8f7b97847eea6b6bab5eb8b61da9130a75fffa1c9152b7d39f77e534ea870281b8e280ea1fdbd49a8f5a7c67d1f512fe7a030e81ae6bd2beed762ad074edcd5d7eb4456a6797911ec78e4d16e7d3ac96b919052a764b7ee4940fd6976346608ad8fed1eb6b0b14d84d802c594b3fd94378a26837df66b328f01cfd144f2e7bc166370bf7a833862173412d2798e8739ee7ef9b0568afab0fc69f66af19864480bf3e74fe2fd2043ec90396e40ab62dc9c1f32dee0e309af7561d2286380ebb497105bde2860d401ccfb4cfcd7047ad32e9408d37f5d0fe9a67bd964d5b138b2546a7d54647467c59384eaa20728cf240c460e36db68afdcf0291135f9d5ff58563f14856fd28534a5478ba2579234b247d0d5862c5742495a2ae18c5ca0d6461d641c5215b07e690280fa3eaf5d392e1d6e2791b181a500964d4bc6c76310e47468ae72edddc3c04d83363514c908624747118']);

        $options = $this->completePurchaseOptions;

        unset($options['transactionId']);

        $response = $this->gateway->completePurchase($options)->send();
    }
}
