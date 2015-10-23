# Omnipay: Sage Pay

**Sage Pay driver for the Omnipay PHP payment processing library**

[![Build Status](https://travis-ci.org/thephpleague/omnipay-sagepay.png?branch=master)](https://travis-ci.org/thephpleague/omnipay-sagepay)
[![Latest Stable Version](https://poser.pugx.org/omnipay/sagepay/version.png)](https://packagist.org/packages/omnipay/sagepay)
[![Total Downloads](https://poser.pugx.org/omnipay/sagepay/d/total.png)](https://packagist.org/packages/omnipay/sagepay)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP 5.3+. This package implements Sage Pay support for Omnipay.

## Installation

Omnipay is installed via [Composer](http://getcomposer.org/). To install, simply add it
to your `composer.json` file:

```json
{
    "require": {
        "omnipay/sagepay": "~2.0"
    }
}
```

And run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

## Basic Usage

The following gateways are provided by this package:

* SagePay_Direct
* SagePay_Server

For general usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay)
repository.

### SagePay Server

Here is some working code from [a stackoverflow answer](http://stackoverflow.com/questions/29370534/does-anyone-have-a-working-example-of-omnipay-and-sagepay-server-or-sagepay-dire):

```
<?php 

use Omnipay\Omnipay;

class PaymentGateway  {

    //live details
    private $live_vendor = 'xxx';
    //test details
    private $test_vendor= 'xxx';

    //payment settings
    private $testMode = true;
    private $api_vendor = '';
    private $gateway = null;

    public function __construct()
    {
        parent::__construct();
        //setup api details for test or live
        if ($this->testMode) :
            $this->api_vendor = $this->test_vendor;
        else :
            $this->api_vendor = $this->live_vendor;
        endif;

        //initialise the payment gateway
        $this->gateway = Omnipay::create('SagePay_Server');
        $this->gateway->setVendor($this->api_vendor);
        $this->gateway->setTestMode($this->testMode);
    }

    public function initiate()
    {
        //get order details
        $orderNo = customFunctionToGetOrderNo(); //get the order number from your system however you store and retrieve it

        $params = array(
            'description'=> 'Online order',
            'currency'=> 'GBP',
            'transactionId'=> $orderNo,
            'amount'=> customFunctionToGetOrderTotal($orderNo)
        );

        $customer = customFunctionToGetCustomerDetails($orderNo);

        $params['returnUrl'] = '/payment-gateway-process/' . $orderNo .  '/'; //this is the Sagepay NotificationURL

        $params['card'] = array(
            'firstName' => $customer['billing_firstname'],
            'lastName' => $customer['billing_lastname'],
            'email' => $customer['billing_email'],
            'billingAddress1' => $customer['billing_address1'],
            'billingAddress2' => $customer['billing_address2'],
            'billingCity' => $customer['billing_town'],
            'billingPostcode' => $customer['billing_postcode'],
            'billingCountry' => $customer['billing_country'],
            'billingPhone' => $customer['billing_telephone'],
            'shippingAddress1' => $customer['delivery_address1'],
            'shippingAddress2' => $customer['delivery_address2'],
            'shippingCity' => $customer['delivery_town'],
            'shippingPostcode' => $customer['delivery_postcode'],
            'shippingCountry' => $customer['delivery_country']
        );

        try {
            $response = $this->gateway->purchase($params)->send();

            if ($response->isSuccessful()) :
                //not using this part
            elseif ($response->isRedirect()) :
                $reference = $response->getTransactionReference();
                customFunctionToSaveTransactionReference($orderNo, $reference);
                $response->redirect();
            else :
                //do something with an error
                echo $response->getMessage();
            endif;
        } catch (\Exception $e) {
            //do something with this if an error has occurred
            echo 'Sorry, there was an error processing your payment. Please try again later.';
        }
    }


    public function processPayment($orderNo)
    {
        $params = array(
            'description'=> 'Online order',
            'currency'=> 'GBP',
            'transactionId'=> $orderNo,
            'amount'=> customFunctionToGetOrderTotal($orderNo)
        );

        $customer = customFunctionToGetCustomerDetails($orderNo);

        $transactionReference = customFunctionToGetTransactionReference($orderNo);

        try {
            $response = $this->gateway->completePurchase(array(
                'transactionId' => $orderNo,
                'transactionReference' => $transactionReference,
            ))->send();

            customFunctionToSaveStatus($orderNo, array('payment_status' => $response->getStatus()));
            customFunctionToSaveMessage($orderNo, array('gateway_response' => $response->getMessage()));

            //encrypt it to stop anyone being able to view other orders
            $encodeOrderNo = customFunctionToEncodeOrderNo($orderNo);
            $response->confirm('/payment-gateway-response/' . $encodeOrderNo);

        } catch(InvalidResponseException $e) {
            // Send "INVALID" response back to SagePay.
            $request = $this->gateway->completePurchase(array());
            $response = new \Omnipay\SagePay\Message\ServerCompleteAuthorizeResponse($request, array());

            customFunctionToSaveStatus($orderNo, array('payment_status' => $response->getStatus()));
            customFunctionToSaveMessage($orderNo, array('gateway_response' => $response->getMessage()));

            redirect('/payment-error-response/');
        }
    }


    public function paymentResponse($encodedOrderNo)
    {
        $orderNo = customFunctionToDecode($encodedOrderNo);
        $sessionOrderNo = customFunctionToGetOrderNo(); 
        if ($orderNo != $sessionOrderNo) :
            //do something here as someone is trying to fake a successful order
        endif;
        $status = customFunctionToGetOrderStatus($orderNo);

        switch(strtolower($status)) :
            case 'ok' :
                customFunctionToHandleSuccess($orderNo);
            break;

            case 'rejected' :
            case 'notauthed' :
                //do something to handle failed payments
            break;

            case 'error' :
               //do something to handle errors

            break;

            default:
                //do something if it ever reaches here

        endswitch;
    }

 }
```

## Support

If you are having general issues with Omnipay, we suggest posting on
[Stack Overflow](http://stackoverflow.com/). Be sure to add the
[omnipay tag](http://stackoverflow.com/questions/tagged/omnipay) so it can be easily found.

If you want to keep up to date with release anouncements, discuss ideas for the project,
or ask more detailed questions, there is also a [mailing list](https://groups.google.com/forum/#!forum/omnipay) which
you can subscribe to.

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/thephpleague/omnipay-sagepay/issues),
or better yet, fork the library and submit a pull request.
