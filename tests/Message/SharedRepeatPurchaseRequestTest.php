<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class SharedRepeatPurchaseRequestTest extends TestCase
{
    /**
     * @var \Omnipay\SagePay\Message\DirectRepeatPurchaseRequest $request
     */
    protected $request;

    public function setUp(): void
    {
        parent::setUp();

        $this->request = new SharedRepeatPurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'amount' => '12.00',
                'currency' => 'EUR',
                'transactionId' => '123',
            )
        );
    }

    public function testSettingOfRelatedTransaction()
    {
        $relatedTransactionRef =
            '{"SecurityKey":"F6AF4AIB1G","TxAuthNo":"1518884596","VPSTxId":"{9EC5D0BC-A816-E8C3-859A-55C1E476E7C2}","VendorTxCode":"D6429BY7x2217743"}';
        $this->request->setTransactionReference($relatedTransactionRef);
        $this->request->setDescription('testSettingOfRelatedTransaction');
        $data = $this->request->getData();

        $this->assertEquals('12.00', $data['Amount'], 'Transaction amount does not match');
        $this->assertEquals('EUR', $data['Currency'], 'Currency code does not match');
        $this->assertEquals('123', $data['VendorTxCode'], 'Transaction ID does not match');
        $this->assertEquals('F6AF4AIB1G', $data['RelatedSecurityKey'], 'Security Key does not match');
        $this->assertEquals('{9EC5D0BC-A816-E8C3-859A-55C1E476E7C2}', $data['RelatedVPSTxId'],
            'Related VPSTxId does not match');
        $this->assertEquals('D6429BY7x2217743', $data['RelatedVendorTxCode'], 'Related VendorTxCode does not match');
        $this->assertEquals('1518884596', $data['RelatedTxAuthNo'], 'Related TxAuthNo does not match');

        $this->assertEquals('REPEAT', $data['TxType']);
        $this->assertEquals('repeat', $this->request->getService());
    }
}
