<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class DirectTokenRequestTest extends TestCase
{
    /**
     * @var \Omnipay\Common\Message\AbstractRequest $request
     */
    protected $request;
    /**
     * @var array
     */
    protected $card;

    public function setUp()
    {
        $this->request = new DirectTokenRegistrationRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'amount' => '12.00',
                'currency' => 'GBP',
                'transactionId' => '123',
                'card' => $this->getValidCard(),
            )
        );
    }

    public function testGetData()
    {
        $data = $this->request->getData();

        $this->assertSame('3.00', $data['VPSProtocol']);
        $this->assertSame('GBP', $data['Currency']);
        $this->assertSame('123', $data['VendorTxCode']);
        $this->assertSame('TOKEN', $data['TxType']);
        $this->assertSame('visa', $data['CardType']);
        $this->assertArrayNotHasKey('AccountType', $data);

        $card = $this->request->getCard();

        $this->assertSame($card->getNumber(), $data['CardNumber']);
        $this->assertSame($card->getCvv(), $data['CV2']);
        $this->assertSame($card->getName(), $data['CardHolder']);
        $this->assertSame($card->getExpiryDate('my'), $data['ExpiryDate']);
    }

    public function testGetDataMastercard()
    {
        $this->request->getCard()->setNumber('5404000000000001');
        $data = $this->request->getData();

        $this->assertSame('mc', $data['CardType']);
    }

    public function testGetDataDinersClub()
    {
        $this->request->getCard()->setNumber('30569309025904');
        $data = $this->request->getData();

        $this->assertSame('dc', $data['CardType']);
    }

}