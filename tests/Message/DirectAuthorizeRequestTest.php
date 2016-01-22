<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class DirectAuthorizeRequestTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->request = new DirectAuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'amount' => '12.00',
                'currency' => 'GBP',
                'transactionId' => '123',
                'card' => $this->getValidCard(),
            )
        );
    }

    public function testGetDataDefaults()
    {
        $data = $this->request->getData();

        $this->assertSame('E', $data['AccountType']);
        $this->assertSame(0, $data['ApplyAVSCV2']);
        $this->assertSame(0, $data['Apply3DSecure']);
    }

    public function testGetData()
    {
        $this->request->setAccountType('M');
        $this->request->setApplyAVSCV2(2);
        $this->request->setApply3DSecure(3);
        $this->request->setDescription('food');
        $this->request->setClientIp('127.0.0.1');
        $this->request->setReferrerId('3F7A4119-8671-464F-A091-9E59EB47B80C');

        $data = $this->request->getData();

        $this->assertSame('M', $data['AccountType']);
        $this->assertSame('food', $data['Description']);
        $this->assertSame('12.00', $data['Amount']);
        $this->assertSame('GBP', $data['Currency']);
        $this->assertSame('123', $data['VendorTxCode']);
        $this->assertSame('127.0.0.1', $data['ClientIPAddress']);
        $this->assertSame(2, $data['ApplyAVSCV2']);
        $this->assertSame(3, $data['Apply3DSecure']);
        $this->assertSame('3F7A4119-8671-464F-A091-9E59EB47B80C', $data['ReferrerID']);
    }

    public function testNoBasket()
    {
        // First with no basket set at all.
        $data = $this->request->getData();
        $this->assertArrayNotHasKey('BasketXML', $data);

        // Then with a basket containing no items.
        $items = new \Omnipay\Common\ItemBag(array());
        $data = $this->request->getData();
        $this->assertArrayNotHasKey('BasketXML', $data);
    }

    public function testBasket()
    {
        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\Common\Item(array(
                'name' => 'Name',
                'description' => 'Description',
                'quantity' => 1,
                'price' => 1.23,
            ))
        ));

        $basketXml = '<basket><item>'
            . '<description>Name</description><quantity>1</quantity>'
            . '<unitNetAmount>1.23</unitNetAmount><unitTaxAmount>0.00</unitTaxAmount>'
            . '<unitGrossAmount>1.23</unitGrossAmount><totalGrossAmount>1.23</totalGrossAmount>'
            . '</item></basket>';

        $this->request->setItems($items);

        $data = $this->request->getData();

        // The element does exist, and must contain the basket XML, with optional XML header and
        // trailing newlines.
        $this->assertArrayHasKey('BasketXML', $data);
        $this->assertContains($basketXml, $data);
    }

    public function testGetDataNoReferrerId()
    {
        // Default value is equivalent to this:
        $this->request->setReferrerId('');

        $data = $this->request->getData();

        $this->assertArrayNotHasKey('ReferrerID', $data);
    }

    public function testGetDataCustomerDetails()
    {
        $card = $this->request->getCard();
        $data = $this->request->getData();

        $this->assertSame($card->getFirstName(), $data['BillingFirstnames']);
        $this->assertSame($card->getLastName(), $data['BillingSurname']);
        $this->assertSame($card->getBillingAddress1(), $data['BillingAddress1']);
        $this->assertSame($card->getBillingAddress2(), $data['BillingAddress2']);
        $this->assertSame($card->getBillingCity(), $data['BillingCity']);
        $this->assertSame($card->getBillingPostcode(), $data['BillingPostCode']);
        $this->assertSame($card->getBillingState(), $data['BillingState']);
        $this->assertSame($card->getBillingCountry(), $data['BillingCountry']);
        $this->assertSame($card->getBillingPhone(), $data['BillingPhone']);

        $this->assertSame($card->getFirstName(), $data['DeliveryFirstnames']);
        $this->assertSame($card->getLastName(), $data['DeliverySurname']);
        $this->assertSame($card->getShippingAddress1(), $data['DeliveryAddress1']);
        $this->assertSame($card->getShippingAddress2(), $data['DeliveryAddress2']);
        $this->assertSame($card->getShippingCity(), $data['DeliveryCity']);
        $this->assertSame($card->getShippingPostcode(), $data['DeliveryPostCode']);
        $this->assertSame($card->getShippingState(), $data['DeliveryState']);
        $this->assertSame($card->getShippingCountry(), $data['DeliveryCountry']);
        $this->assertSame($card->getShippingPhone(), $data['DeliveryPhone']);
    }

    public function testGetDataCustomerDetailsIgnoresStateOutsideUS()
    {
        $card = $this->request->getCard();
        $card->setBillingCountry('UK');
        $card->setShippingCountry('NZ');

        $data = $this->request->getData();

        // these must be empty string, not null
        // (otherwise Guzzle ignores them, and SagePay throws a fit)
        $this->assertSame('', $data['BillingState']);
        $this->assertSame('', $data['DeliveryState']);
    }

    public function testGetDataVisa()
    {
        $this->request->getCard()->setNumber('4929000000006');
        $data = $this->request->getData();

        $this->assertSame('visa', $data['CardType']);
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

    public function testGetDataNullBillingAddress2()
    {
        $card = $this->request->getCard();

        // This emulates not setting the billing address 2 at all
        // (it defaults to null).
        $card->setBillingAddress2(null);

        $data = $this->request->getData();

        $this->assertNull($data['BillingAddress2']);

        // This tests that the BillingAddress2 may be left unset,
        // which defaults to null. When it is sent to SagePay, it gets
        // converted to an empty string. I'm not clear how that would be
        // tested.
    }

    public function testBasketWithNoDiscount()
    {
        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\Common\Item(array(
                'name' => 'Name',
                'description' => 'Description',
                'quantity' => 1,
                'price' => 1.23,
            ))
        ));

        $this->request->setItems($items);
        $data = $this->request->getData();
        // The element does exist, and must contain the basket XML, with optional XML header and
        // trailing newlines.
        $this->assertArrayHasKey('BasketXML', $data);
        $this->assertNotContains('<discount>', $data['BasketXML']);
    }

    public function testBasketWithOneDiscount()
    {
        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\Common\Item(array(
                'name' => 'Name',
                'description' => 'Description',
                'quantity' => 1,
                'price' => 10.0,
            )),
            array(
                'name' => 'One Discount',
                'description' => 'My Offer',
                'quantity' => 1,
                'price' => -4,
            )
        ));

        $this->request->setItems($items);
        $data = $this->request->getData();
        // The element does exist, and must contain the basket XML, with optional XML header and
        // trailing newlines.
        $this->assertArrayHasKey('BasketXML', $data);
        $this->assertContains('<discounts>', $data['BasketXML']);
    }

    public function testBasketWithTwoDiscount()
    {
        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\Common\Item(array(
                'name' => 'Name',
                'description' => 'Description',
                'quantity' => 1,
                'price' => 1.23,
            )),
            array(
                'name' => 'One Discount',
                'description' => 'My Offer',
                'quantity' => 1,
                'price' => -4,
            ),
            array(
                'name' => 'Second Discount',
                'description' => 'My 2nd Offer',
                'quantity' => 1,
                'price' => -5,
            )
        ));

        $expected = '<basket><item>'
            . '<description>Name</description><quantity>1</quantity>'
            . '<unitNetAmount>1.23</unitNetAmount><unitTaxAmount>0.00</unitTaxAmount>'
            . '<unitGrossAmount>1.23</unitGrossAmount><totalGrossAmount>1.23</totalGrossAmount>'
            . '</item><discounts><discount><fixed>4</fixed><description>One Discount</description></discount><discount><fixed>5</fixed><description>Second Discount</description></discount></discounts></basket>';

        $this->request->setItems($items);
        $data = $this->request->getData();
        // The element does exist, and must contain the basket XML, with optional XML header and
        // trailing newlines.
        $this->assertArrayHasKey('BasketXML', $data);
        $this->assertContains($expected, $data['BasketXML']);
    }

    public function testBasketWithNotEscapedName()
    {
        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\Common\Item(array(
                'name' => "Denise's Very Odd & Wierd name?",
                'description' => 'Description',
                'quantity' => 1,
                'price' => 1.23,
            )),
            array(
                'name' => 'One Discount',
                'description' => 'My Offer',
                'quantity' => 1,
                'price' => 4,
            ),
            array(
                'name' => 'Second Discount',
                'description' => 'My 2nd Offer',
                'quantity' => 1,
                'price' => 5,
            )
        ));

        $expected = "<basket><item><description>Denise's Very Odd & Wierd name?</description><quantity>1</quantity><unitNetAmount>1.23</unitNetAmount><unitTaxAmount>0.00</unitTaxAmount><unitGrossAmount>1.23</unitGrossAmount><totalGrossAmount>1.23</totalGrossAmount></item><item>
                    <description>One Discount</description><quantity>1</quantity><unitNetAmount>4</unitNetAmount><unitTaxAmount>0.00</unitTaxAmount><unitGrossAmount>4</unitGrossAmount><totalGrossAmount>4</totalGrossAmount></item><item><description>Second Discount</description><quantity>1</quantity><unitNetAmoubnt>5</unitNetAmoubnt>
                    <totalGrossAmount>5</totalGrossAmount>";

        $this->request->setItems($items);
        $data = $this->request->getData(); 


        $this->assertNotContains($expected , $data['BasketXML'], "Should fail as name was not escaped" );

    }

    public function testBasketWithEscapedName()
    {
        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\Common\Item(array(
                'name' => "<Denise's Very Odd & Wierd 'name'?>",
                'description' => 'Description',
                'quantity' => 1,
                'price' => 1.23,
            )),
            array(
                'name' => 'One Discount',
                'description' => 'My Offer',
                'quantity' => 1,
                'price' => 4,
            ),
            array(
                'name' => 'Second Discount',
                'description' => 'My 2nd Offer',
                'quantity' => 1,
                'price' => 5,
            )
        ));


        $this->request->setItems($items);
        $data = $this->request->getData();

        $this->assertArrayHasKey('BasketXML', $data, "Should create XML");
        $this->assertContains($data['BasketXML'], $data, "Should not fail as name was escaped" );
    }
}
