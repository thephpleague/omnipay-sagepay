<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;

class DirectAuthorizeRequestTest extends TestCase
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

        $this->request = new DirectAuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());
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

        $this->assertSame('DEFERRED', $data['TxType']);
        $this->assertSame('vspdirect-register', $this->request->getService());

        // If we have not explicitly set the CreateToken flag, then it remains
        // undefined. This allows it to default when creating a transaction
        // according to whether we are using a single-use token or a more
        // permanent cardReference.

        $this->assertArrayNotHasKey('CreateToken', $data);

        $this->assertSame('EN', $data['Language']);
    }

    public function testGetData()
    {
        $this->request->setAccountType('M');
        $this->request->setApplyAVSCV2(2);
        $this->request->setApply3DSecure(3);
        $this->request->setDescription('food');
        $this->request->setClientIp('127.0.0.1');
        $this->request->setReferrerId('3F7A4119-8671-464F-A091-9E59EB47B80C');
        $this->request->setLanguage('EN');
        $this->request->setVendorData('Vendor secret codes');
        $this->request->setCardholderName('Mr E User');
        $this->request->setCreateToken(true);

        $data = $this->request->getData();

        $this->assertSame('M', $data['AccountType']);
        $this->assertSame('food', $data['Description']);
        $this->assertSame('12.00', $data['Amount']);
        $this->assertSame('GBP', $data['Currency']);
        $this->assertSame('123', $data['VendorTxCode']);
        $this->assertSame('127.0.0.1', $data['ClientIPAddress']);
        $this->assertSame(2, $data['ApplyAVSCV2']);
        $this->assertSame(3, $data['Apply3DSecure']);
        $this->assertSame('EN', $data['Language']);
        $this->assertSame('Vendor secret codes', $data['VendorData']);
        $this->assertSame('Mr E User', $data['CardHolder']);
        $this->assertSame(1, $data['CreateToken']);
        $this->assertSame(self::SURCHARGE_XML, $data['surchargeXml']);
    }

    public function testNoBasket()
    {
        // First with no basket set at all.
        $data = $this->request->getData();
        $this->assertArrayNotHasKey('BasketXML', $data);

        // Then with a basket containing no items.
        $items = new \Omnipay\Common\ItemBag(array());
        $this->request->setItems($items);
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
        $this->assertContains($basketXml, $data['BasketXML']);
    }

    public function testBasketExtendItem()
    {
        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\SagePay\Extend\Item(array(
                'name' => 'Name',
                'description' => 'Description',
                'quantity' => 1,
                'price' => 1.23,
                'vat' => 0.205,
            ))
        ));

        $basketXml = '<basket><item>'
            . '<description>Name</description><quantity>1</quantity>'
            . '<unitNetAmount>1.23</unitNetAmount><unitTaxAmount>0.205</unitTaxAmount>'
            . '<unitGrossAmount>1.435</unitGrossAmount><totalGrossAmount>1.435</totalGrossAmount>'
            . '</item></basket>';

        $this->request->setItems($items);

        $data = $this->request->getData();

        // The element does exist, and must contain the basket XML, with optional XML header and
        // trailing newlines.
        $this->assertArrayHasKey('BasketXML', $data);
        $this->assertContains($basketXml, $data['BasketXML']);
    }

    public function testGetDataNoReferrerId()
    {
        // Default value is equivalent to this:
        $this->request->setReferrerId('');

        $data = $this->request->getData();

        $this->assertArrayNotHasKey('ReferrerID', $data);
    }

    public function testFilterClientIp()
    {
        // Valid IPv4 (no filter)
        $this->request->setClientIp('1.2.3.4');
        $this->assertSame($this->request->getClientIp(), '1.2.3.4');

        // Invalid IPv4 (filtered)
        $this->request->setClientIp('a.b.c.d');
        $this->assertNull($this->request->getClientIp());

        // Valid IPv6 (filtered)
        $this->request->setClientIp('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        $this->assertNull($this->request->getClientIp());
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

        // The card type to be sent is always upper case.
        $this->assertSame('VISA', $data['CardType']);
    }

    public function testGetDataMastercard()
    {
        $this->request->getCard()->setNumber('5404000000000001');
        $data = $this->request->getData();

        // The card type to be sent is always upper case.
        $this->assertSame('MC', $data['CardType']);
    }

    public function testGetDataDinersClub()
    {
        $this->request->getCard()->setNumber('30569309025904');
        $data = $this->request->getData();

        // This card type does not involve any mapping.
        $this->assertSame('DC', $data['CardType']);
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

    public function testMixedBasketWithSpecialChars()
    {
        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\Common\Item(array(
                'name' => "Denisé's Odd & Wierd £name? #12345678901234567890123456789012345678901234567890123456789012345678901234567890",
                'description' => 'Description',
                'quantity' => 2,
                'price' => 4.23,
            )),
            array(
                'name' => "Denisé's \"Odd\" & Wierd £discount? #",
                'description' => 'My Offer',
                'quantity' => 2,
                'price' => -0.10,
            ),
            array(
                // 101 character name
                'name' => '12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901',
                'description' => 'My 2nd Offer',
                'quantity' => 1,
                'price' => -1.60,
            )
        ));

        // Names/descriptions should be max 100 characters in length, once invalid characters have been removed.
        $expected = '<basket><item>'
            . '<description>Denis\'s Odd &amp; Wierd name 123456789012345678901234567890123456789012345678901234567890123456789012345</description><quantity>2</quantity>'
            . '<unitNetAmount>4.23</unitNetAmount><unitTaxAmount>0.00</unitTaxAmount>'
            . '<unitGrossAmount>4.23</unitGrossAmount><totalGrossAmount>8.46</totalGrossAmount>'
            . '</item><discounts>'
            . '<discount><fixed>0.2</fixed><description>Denis\'s "Odd"  Wierd discount? #</description></discount>'
            . '<discount><fixed>1.6</fixed><description>1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890</description></discount>'
            . '</discounts></basket>';

        $this->request->setItems($items);
        $data = $this->request->getData();

        $this->assertArrayHasKey('BasketXML', $data);
        $this->assertContains($expected, $data['BasketXML'], 'Basket XML does not match the expected output');
    }

    public function testNonXmlBasket()
    {
        $this->request->setUseOldBasketFormat(true);

        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\SagePay\Extend\Item(array(
                'name' => "Pioneer NSDV99 DVD-Surround Sound System",
                'quantity' => 3,
                'price' => 4.35,
            )),
        ));

        $this->request->setItems($items);
        $data = $this->request->getData();

        $this->assertArrayNotHasKey('BasketXML', $data);
        $this->assertSame('1:Pioneer NSDV99 DVD-Surround Sound System:3:4.35::4.35:13.05', $data['Basket']);
    }

    public function testNonXmlBasketWithVat()
    {
        $this->request->setUseOldBasketFormat(true);

        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\SagePay\Extend\Item(array(
                'name' => "Pioneer NSDV99 DVD-Surround Sound System",
                'quantity' => 3,
                'price' => 4.35,
                'vat' => 2
            )),
        ));

        $this->request->setItems($items);
        $data = $this->request->getData();

        $this->assertArrayHasKey('Basket', $data);
        $this->assertArrayNotHasKey('BasketXML', $data);

        $this->assertSame('1:Pioneer NSDV99 DVD-Surround Sound System:3:4.35:2:6.35:19.05', $data['Basket']);
    }

    public function testNonXmlBasketWithProductCode()
    {
        $this->request->setUseOldBasketFormat(true);

        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\SagePay\Extend\Item(array(
                'name' => "Pioneer NSDV99 DVD-Surround Sound System",
                'quantity' => 3,
                'price' => 4.35,
                'vat' => 2,
                'productCode' => 'DVD-123'
            )),
        ));

        $this->request->setItems($items);
        $data = $this->request->getData();

        $this->assertSame('1:[DVD-123]Pioneer NSDV99 DVD-Surround Sound System:3:4.35:2:6.35:19.05', $data['Basket']);
    }

    public function testNonXmlBasketWithSpecialAndNonSpecialCharacters()
    {
        $this->request->setUseOldBasketFormat(true);

        $items = new \Omnipay\Common\ItemBag(array(
            new \Omnipay\SagePay\Extend\Item(array(
                // [] and ::: are reserved
                'name' => "[SKU-ABC]Pioneer::: NSDV99 DVD-Surround Sound System .-{};_@()",
                'quantity' => 3,
                'price' => 4.35,
                'vat' => 2,
            )),
        ));

        $this->request->setItems($items);
        $data = $this->request->getData();

        $this->assertSame('1:[SKU-ABC]Pioneer NSDV99 DVD-Surround Sound System .-{};_@():3:4.35:2:6.35:19.05', $data['Basket']);
    }

    public function testCreateTokenCanBeSetInRequest()
    {
        $this->request->setCreateToken(true);
        $data = $this->request->getData();

        $this->assertSame(1, $data['CreateToken']);
    }

    /**
     * @dataProvider tokenSetterProvider
     *
     * Now disabled for consistency of getters and setters.
     * The token can be any value that can be cast to boolean,
     * and is set to 0 or 1 only at time of use.
     */
    public function testCreateTokenCanOnlyBeOneOrZeroInRequest($parameter, $expectation)
    {
        $this->request->setCreateToken($parameter);
        $data = $this->request->getData();

        $this->assertSame(
            $expectation,
            isset($data['CreateToken']) ? $data['CreateToken'] : null
        );
    }

    public function testExistingTokenCanBeSet()
    {
        $token = '{ABCDEF}';
        $this->request->setToken($token);

        $data = $this->request->getData();
        $this->assertSame($token, $data['Token']);

        // If using a "token" then it is assumed to be single-use by default.
        $this->assertSame(0, isset($data['StoreToken']) ? $data['StoreToken'] : 0);
    }

    public function testExistingCardReferenceCanBeSet()
    {
        $token = '{ABCDEF}';
        $this->request->setCardReference($token);

        $data = $this->request->getData();
        $this->assertSame($token, $data['Token']);

        // If using a "cardReference" then it is assumed to be permanent by default.
        $this->assertSame(1, $data['StoreToken']);
    }

    /**
     * This has been turned on its head: if a token is provided, then that
     * takes priority and the "createToken" flag is ignored.
     */
    public function testExistingTokenCannotBeSetIfCreateTokenIsTrue()
    {
        $this->request->setCreateToken(true);
        $this->request->setToken('{ABCDEF}');

        $data = $this->request->getData();

        $this->assertArrayNotHasKey('CreateToken', $data);
        $this->assertSame('{ABCDEF}', $data['Token']);
    }

    public function testStoreTokenCanOnlyBeSetIfExistingTokenIsSetInRequest()
    {
        $this->request->setToken('{ABCDEF}');
        $this->request->setStoreToken(true);
        $data = $this->request->getData();

        $this->assertSame(1, $data['StoreToken']);
    }

    public function testStoreTokenIsUnsetIfThereIsNoExistingTokenSetInRequest()
    {
        $this->request->setStoreToken(true);
        $data = $this->request->getData();

        $this->assertArrayNotHasKey('StoreToken', $data);
    }

    /**
     * @dataProvider tokenSetterProvider
     * No longer applies; the storeToken value is cast to bool on use.
     */
    public function testStoreTokenCanOnlyBeOneOrZeroIfSetInRequest($parameter, $expectation)
    {
        $this->request->setToken('{ABCDEF}');

        $this->request->setStoreToken($parameter);
        $data = $this->request->getData();

        $this->assertSame($expectation, isset($data['StoreToken']) ? $data['StoreToken'] : null);
    }

    public function tokenSetterProvider()
    {
        return array(
            array(1, 1),
            array('1', 1),
            array(true, 1),
            array('some string', 1),
            array(array('something'), 1),
            array(0, 0),
            array('0', 0),
            array(false, 0),
            array('', 0),
            array(null, null),
            array(array(), 0)
        );
    }
}
