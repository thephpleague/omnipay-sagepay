<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class DirectPurchaseRequestTest extends DirectAuthorizeRequestTest
{
    // VISA incurrs a surcharge of 2.5% when used.
    const SURCHARGE_XML = '<surcharges><surcharge>'
        . '<paymentType>VISA</paymentType><percentage>2.50</percentage>'
        . '</surcharge></surcharges>';

    /**
     * @var DirectAuthorizeRequest
     */
    protected $request;

    public function setUp()
    {
        parent::setUp();

        $this->request = new DirectPurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->request->initialize(
            array(
                // Money as Omnipay 3.x Money object, combining currency and amount
                // Omnipay 3.0-RC2 no longer accepts a money object.
                'amount' => '12.00', //Money::GBP(1200),
                'currency' => 'GBP',
                'transactionId' => '123',
                'surchargeXml' => self::SURCHARGE_XML,
                'card' => $this->getValidCard(),
                'language' => 'EN',
            )
        );
    }

    public function testGetDataDefaults()
    {
        $data = $this->request->getData();

        $this->assertSame('E', $data['AccountType']);
        $this->assertSame(0, $data['ApplyAVSCV2']);
        $this->assertSame(0, $data['Apply3DSecure']);

        $this->assertSame('PAYMENT', $data['TxType']);
        $this->assertSame('vspdirect-register', $this->request->getService());

        // If we have not explicitly set the CreateToken flag, then it remains
        // undefined. This allows it to default when creating a transaction
        // according to whether we are using a single-use token or a more
        // permanent cardReference.

        $this->assertArrayNotHasKey('CreateToken', $data);

        $this->assertSame('EN', $data['Language']);
    }
}
