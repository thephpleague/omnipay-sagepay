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

### Supported Methods

Sage Pay Direct Methods:

* authorize() - with completeAuthorize for 3D Secure and PayPal redirect
* purchase() - with completeAuthorize for 3D Secure and PayPal redirect
* registerToken() - standalone register of a card token

Sage Pay Server Methods:

* authorize()
* purchase()
* acceptNotification() - Notification Handler for authorize, purchase and standalone token registration
* registerToken() - standalone register of a card token

Sage Pay Shared Methods (for both Direct and Server):

* capture()
* refund()
* abort() - abort an authorization before it is captured
* repeatAuthorize() - new authorization based on past transaction
* repeatPurchase() - new purchase based on past transaction
* void() - void a purchase
* removeToken() - remove a card token

### Basket format

Sagepay currently supports two different formats for sending cart/item information to them:  
 - [BasketXML](http://www.sagepay.co.uk/support/12/36/protocol-3-00-basket-xml)
 - [Basket](http://www.sagepay.co.uk/support/error-codes/3021-invalid-basket-format-invalid)

These are incompatible with each other, and cannot be both sent in the same transaction. *BasketXML* is the most modern format, and is the default used by this driver. *Basket* is an older format which may be deprecated one day, but is also the only format currently supported by some of the Sage accounting products (Line 50, etc) which can pull transaction data directly from Sagepay. Therefore for users who require this type of integration, an optional parameter `useOldBasketFormat` with a value of `true` can be passed in the driver's `initialize()` method.

## Notification Handler

> **NOTE:** The notification handler was previously handled by the SagePay_Server `completeAuthorize`,
  `completePurchase` and `completeRegistration` methods. The notification handler replaces all of these.
  The old methods have been left - for the remaining life of OmniPay 2.x -
  for use in legacy applications.
  The recomendation is to use the newer `acceptNotification` handler
  now, which is simpler and will be more consistent with other gateways.

The `SagePay_Server` gateway uses a notification callback to receive the results of a payment or authorisation.
(Sage Pay Direct does not use the notification handler.)
The URL for the notification handler is set in the authorize or payment message:

```php
// The Server response will be a redirect to the Sage Pay CC form.
// This is a Sage Pay Server Purchase request.

$response = $gateway->purchase(array(
    'amount' => '9.99',
    'currency' => 'GBP',
    'card' => $card, // Just the name and address, NOT CC details.
    'notifyUrl' => route('sagepay.server.notify'), // The route to your application's notification handler.
    'transactionId' => $transactionId,
    'description' => 'test',
    'items' => $items,
))->send();

// Before redirecting, save `$response->transactionReference()` in the database, indexed
// by `$transactionId`.
// Note that at this point `transactionReference` is not yet complete for the Server transaction,
// but must be saved in the database for the notification handler to use.

if ($response->isRedirect()) {
    // Go to Sage Pay to enter CC details.
    // While your user is there, the notification handler will be called.
    $response->redirect();
}
```

Your notification handler needs to do four things:

1. Look up the saved transaction in the database to retrieve the `transactionReference`.
2. Validate the signature of the recieved notification to protect against tampering.
3. Update your saved transaction with the results, including the updated - i.e. more complete -
   `transactionReference` if successful.
4. Respond to Sage Pay to indicate that you accept the result, reject the result or don't
   believe the notifcation was valid. Also tell Sage Pay where to send the user next.

This is a back-channel, so has no access to the end user's session.

The acceptNotification gateway is set up simply. The `$request` will capture the POST data sent by Sage Pay:

```php
$gateway = OmniPay\OmniPay::create('SagePay_Server');
$gateway->setVendor('your-vendor-name');
$gateway->setTestMode(true); // If testing
$request = $gateway->acceptNotification();
```

Your original `transactionId` is available to look up the transaction in the database:

```php
// Use this to look up the `$transactionReference` you saved:
$transactionId = $request->getTransactionId();
```

Now the signature can be checked:

```php
// The transactionReference contains a one-time token known as the `securitykey` that is
// used in the signature hash. You can alternatively `setSecurityKey('...')` if you saved
// that as a separate field.
$request->setTransactionReference($transactionReference);

// Get the response message ready for returning.
$response = $request->send();

if (! $request->isValid()) {
    // Respond to Sage Pay indicating we are not accepting anything about this message.
    // You might want to log `$request->getData()` first, for later analysis.

    $response->invalid($nextUrl, 'Signature not valid - goodbye');
}
```

If you were not able to look up the transaction or the transaction is in the wrong state,
then indicate this with an error. Note an "error" is to indicate that although the notification
appears to be legitimate, you do not accept it or cannot handle it for any reason:

```php
$response->error($nextUrl, 'This transaction does not exist on the system');
```

> **Note:** it has been observed that the same notification message may be sent
  by Sage Pay multiple times.
  If this happens, then return the same response you sent the first time.
  So if you have confirmed a successful payment, then if you get another
  identical response for the transaction, then return `confirm()` again.

If you accept the notification, then you can update your local records and let Sage Pay know:

```php
// All raw data - just log it for later analysis:
$request->getData();

// Save the final transactionReference against the transaction in the database. It will
// be needed if you want to capture the payment (for an authorize) or void or refund the
// payment later.
$finalTransactionReference = $response->getTransactionReference();

// The payment or authorisation result:
// Result is $request::STATUS_COMPLETED, $request::STATUS_PENDING or $request::STATUS_FAILED
$request->getTransactionStatus();

// If you want more detail, look at the raw data. An error message may be found in:
$request->getMessage();

// Now let Sage Pay know you have got it and saved the details away safely:
$response->confirm($nextUrl);
```

That's it. The `$nextUrl` is where you want Sage Pay to send the user to next.
It will often be the same URL whether the transaction was approved or not,
since the result will be safely saved in the database.

## Support

If you are having general issues with Omnipay, we suggest posting on
[Stack Overflow](http://stackoverflow.com/). Be sure to add the
[omnipay tag](http://stackoverflow.com/questions/tagged/omnipay) so it can be easily found.

If you want to keep up to date with release anouncements, discuss ideas for the project,
or ask more detailed questions, there is also a [mailing list](https://groups.google.com/forum/#!forum/omnipay) which
you can subscribe to.

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/thephpleague/omnipay-sagepay/issues),
or better yet, fork the library and submit a pull request.
