# Omnipay: Sage Pay

**Sage Pay driver for the Omnipay PHP payment processing library**

[![Build Status](https://travis-ci.org/thephpleague/omnipay-sagepay.png?branch=master)](https://travis-ci.org/thephpleague/omnipay-sagepay)
[![Latest Stable Version](https://poser.pugx.org/omnipay/sagepay/version.png)](https://packagist.org/packages/omnipay/sagepay)
[![Total Downloads](https://poser.pugx.org/omnipay/sagepay/d/total.png)](https://packagist.org/packages/omnipay/sagepay)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP 5.3+. This package implements Sage Pay support for Omnipay. This version only supports
PHP 5.4+.

This is the `2.x` branch of Omnipay.
For the `3.x` branch, please visit https://github.com/thephpleague/omnipay-sagepay/tree/master

Table of Contents
=================

   * [Omnipay: Sage Pay](#omnipay-sage-pay)
   * [Table of Contents](#table-of-contents)
   * [Installation](#installation)
   * [Basic Usage](#basic-usage)
   * [Supported Methods](#supported-methods)
      * [Sage Pay Direct Methods](#sage-pay-direct-methods)
         * [Direct createCard()](#direct-createcard)
      * [Sage Pay Server Methods](#sage-pay-server-methods)
         * [Server Gateway](#server-gateway)
         * [Server authorize()/purchase()](#server-authorizepurchase)
         * [Server createCard()](#server-createcard)
      * [Sage Pay Shared Methods (for both Direct and Server):](#sage-pay-shared-methods-for-both-direct-and-server)
         * [Direct/Server deleteCard()](#directserver-deletecard)
   * [Token Billing](#token-billing)
      * [Generating a Token or CardReference](#generating-a-token-or-cardreference)
      * [Using a Token or CardRererence](#using-a-token-or-cardrererence)
   * [Basket format](#basket-format)
      * [Sage 50 Accounts Software Integration](#sage-50-accounts-software-integration)
   * [Account Types](#account-types)
   * [Sage Pay Server Notification Handler](#sage-pay-server-notification-handler)
   * [Support](#support)

# Installation

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

# Basic Usage

The following gateways are provided by this package:

* SagePay_Direct
* SagePay_Server

For general usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay)
repository.

# Supported Methods

## Sage Pay Direct Methods

Sage Pay Direct is a server-to-server protocol, with all credit card details
needing to pass through your application for forwarding on to the gateway.
You must be aware of the PCI implications of handling credit card details
if using this API.

* authorize() - with completeAuthorize for 3D Secure and PayPal redirect
* purchase() - with completeAuthorize for 3D Secure and PayPal redirect
* createCard() - explicit "standalone" creation of a cardReference or token

*Note: PayPal is not implemented in this driver at this time.*

### Direct createCard()

This will create a card reference with no authorisation.
If you want to authorise an amount on the card *and* get a cardReference
for repeated use of the card, then use the `authorize()` method with the
`createToken` flag set.

Sample code using Sage Pay Direct to create a card reference:

```php
use Omnipay\Omnipay;
use Omnipay\CreditCard;

$gateway = OmniPay::create('SagePay\Direct');

$gateway->setVendor('your-vendor-code');
$gateway->setTestMode(true); // For test account

// The minimal card details to save to the gateway.
// The CVV is optional. However it can be supplied later when
// transactions are being initiated, though that is not advised
// as the CVV will need to go through your site to be added to
// the transaction.

$card = new CreditCard([
    'firstName' => 'Joe',
    'lastName' => 'Bloggs',
    'number' => '4929000000006',
    'expiryMonth' => '12',
    'expiryYear' => '2020',
    'cvv' => '123',
]);

// Send the request.

$request = $gateway->createCard([
    'currency' => 'GBP',
    'card' => $card,
]);

$response = $request->send();

// There will be no need for any redirect (e.g. 3D Secure), since the
// card is not being authorised at this point.

if ($response->isSuccessful()) {
    $cardReference = $response->getCardReference();
    // or if you prefer to treat it as a single-use token:
    $token = $response->getToken();
}
```

## Sage Pay Server Methods

Sage Pay Server captures any credit card details in forms hosted by the
Sage Pay gateway, either by sending the user to the gateway or loading the
hosted forms in iframes. This is the preferred and safest API to use.

Sage Pay Server uses your IP address to authenticate backend access to the
gateway, and it also needs to a public URL that it can send back-channel
notifications to. This makes development on a localhost server difficult.

* authorize()
* purchase()
* acceptNotification() - Notification Handler for authorize, purchase and explicit cardReference registration
* createCard() - explicit "standalone" creation of a cardReference or token

### Server Gateway

All Sage Pay Server methods start by creating the gateway object, which we
will store in `$gateway` here. Note there are no secrets or passwords that need
to be set, as the gateway uses your server's IP address as its main method of
authenticating your application.

The gateway object is minimally created like this:

```php
use Omnipay\Omnipay;

$gateway = OmniPay::create('SagePay\Server');

$gateway->setVendor('your-vendor-code');
$gateway->setTestMode(true); // For a test account
```

### Server authorize()/purchase()

This method authorises a payment against a credit or debit card.
A `cardToken` or `cardReference` previously captured, can be used here, and only
the user's CVV will be captured, but the overall flow will remain the same.

The `$creditCard` object will provide the billing and shipping details:

```php
use Omnipay\Common\CreditCard;

$creditCard = new CreditCard([
    'firstName' => 'Joe',
    'lastName' => 'Bloggs',
    //
    'billingAddress1' => 'Billing Address 1',
    'billingAddress2' => 'Billing Address 2',
    //'billingState' => '',
    'billingCity' => 'Billing City',
    'billingPostcode' => 'BPOSTC',
    'billingCountry' => 'GB',
    'billingPhone' => '01234 567 890',
    //
    'email' =>  'test@example.com',
    'clientIp' => '123.123.123.123',
    //
    'shippingAddress1' => '99',
    'shippingState' => 'NY',
    'shippingCity' => 'City1',
    'shippingPostcode' => 'SPOSTC',
    'shippingCountry' => 'US',
    'shippingPhone' => '01234 567 890 SS',
]);
```

* The country must be a twe-character ISO 3166 code.
* The state will be a two-character ISO code, and is mandatory if the country is "US".
* The state will be ignored if the country is not "US".
* Adddress2 is optional, but all other fields are mandatory.
* The postcode is optional for Republic of Ireland "IE".
* You can use UTF-8. Only joking! This gateway lives on an extended ASCII ISO 8859-1 back end. Really.
  Do any characterset conversions in your merchant site to avoid surprises.

```php
// Create a unique transaction ID to track this transaction.
$transactionId = {create a unique transaction id};

// Custom surcharges can be added here.
// You must construct the XML string; there is no XML builder in this driver
// at this time. Length is very limited, so keep it compact.
$surchargeXml = '<surcharges>'
        . '<surcharge>'
            . '<paymentType>VISA</paymentType>'
            . '<percentage>5.20</percentage>'
        . '</surcharge>'
    . '</surcharges>';

// Send the authorize request.
// Some optional parameters are shown commented out.
$response = $gateway->authorize(array(
    'amount' => '9.99', // A bargain!
    'currency' => 'GBP',
    'card' => $card,
    'notifyUrl' => 'http://example.com/your/notify.php',
    'transactionId' => $transactionId,
    'description' => 'Mandatory description',
    // 'items' => $items,
    // 'cardReference' => '{4E50F334-9D42-9946-2B0B-ED70B2421D48}',
    // 'surchargeXml' => $surchargeXml,
    // 'token' => $token,
    // 'cardReference' => $cardReference,
))->send();

// Create storage for this transaction now, indexed by the transaction ID.
// We will need to access it in the notification handler.
// The reference given by `$response->getTransactionReference()` must be stored.

// Now decide what to do next, based on the response.
if ($response->isSuccessful()) {
    // The transaction is complete and successful and no further action is needed.
    // This may happen if a cardReference has been supplied, having captured
    // the card reference with a CVV and using it for the first time. The CVV will
    // only be kept by the gateway for this first authorisation. This also assumes
    // 3D Secure is turned off.
} elseif ($response->isRedirect()) {
    // Redirect to offsite payment gateway to capture the users credit card
    // details.
    // If a cardReference was provided, then only the CVV will be asked for.
    // 3D Secure will be performed here too, if enabled.
    // Once the user is redirected to the gateway, the results will be POSTed
    // to the [notification handler](#sage-pay-server-notification-handler).
    // The handler will then inform the gateway where to finally return the user
    // to on the merchant site.

    $response->redirect();
} else {
    // Something went wrong; get the message.
    // The error may be a simple validation error on the address details.
    // Catch those and allow the user to correct the details and submit again.
    // This is a particular pain point of Sage Pay Server.
    $reason = $response->getMessage();
}
```

### Server createCard()

When creating a cardReference, for Sage Pay Server the reference will be available
only in the notification callback.

Sample code using Sage Pay Server to create a card reference:

```php
// The transaction ID is used to store the result in the notify callback.
// Create storage for this transaction now, indexed by the transaction ID.
$transactionId = {create a unique transaction id};

$request = $gateway->createCard([
    'currency' => 'GBP',
    'notifyUrl' => {notify callback URL},
    'transactionId' => $transactionId,
    'iframe' => true, // TRUE if the offsite form is to go into an iframe
]);

$response = $request->send();

if ($response->isSuccessful()) {
    // Should never happen for Sage Pay Server, since the user will always
    // be asked to go off-site to enter their credit card details.
} elseif ($response->isRedirect()) {
    // Redirect to offsite payment gateway to capture the users credit card
    // details. Note that no address details are needed, nor are they captured.

    // Here add the $response->getTransactionReference() to the stored transaction,
    // as the notification handler will need it for checking the signature of the
    // notification it receives.

    $response->redirect();
} else {
    $reason = $response->getMessage();
}
```

At this point the user will be redirected to enter their CC details.
The details will be held by the gateway and a token sent to the notification
handler, along with the `transactionId`.
The notification handler needs to store the `cardReference` or `token` referenced by
the `transactionId` then acknowledge the acceptance and provide a final URL the user
is taken to.

If using an iframe for the hosted credit card form, then on return to the final
redirect URL (provided by the notification handler) it is your site's responsibility
to break out of the iframe.

## Sage Pay Shared Methods (for both Direct and Server):

* capture()
* refund()
* abort() - abort an authorization before it is captured
* repeatAuthorize() - new authorization based on past transaction
* repeatPurchase() - new purchase based on past transaction
* void() - void a purchase
* deleteCard() - remove a cardReference or token from the accout

### Direct/Server deleteCard()

This is one of the simpler messages:

```php
use Omnipay\Omnipay;
use Omnipay\CreditCard;

$gateway = OmniPay::create('SagePay\Direct');
// or
$gateway = OmniPay::create('SagePay\Server');

$gateway->setVendor('your-vendor-code');
$gateway->setTestMode(true); // For test account

// Send the request.
$request = $gateway->deleteCard([
    'cardReference' => $cardReference,
]);

$response = $request->send();

// There will be no need for any redirect (e.g. 3D Secure), since no
// authorisation is being done.
if ($response->isSuccessful()) {
    $message = $response->getMessage();
    // "2017 : Token removed successfully."
}
```

# Token Billing

Sage Pay Server and Direct support the ability to store a credit card detail on
the gateway, referenced by a token, for later use or reuse.
The token can be single-use, or permanently stored (until its expiry date or
explicit removal).

Whether a token is single-use or permanent, depends on how it is *used*, and not
on how it is generated. This is important to understand, and is explained in more
detail below.

## Generating a Token or CardReference

A token can be generated explicitly, with no authorisation, or it can be generated
as a part of a transaction:

* `$gateway->createCard()` - message used to create a card token explicitly/standalone.
* `$request->setCreateToken()` - transaction option to generate a token with the transaction.

If created explicitly, then a CVV can be provided, and that will be stored against the token
until the token is first used to make a payment. If the cardReference is reused after the first
payment, then a CVV must be supplied each time (assuming your rules require the CVV to be present).
If using Sage Pay Server, then the user will be prompted for a CVV on subsequent uses of
the cardReference.

If creating a `token` or `cardReference` with a transaction, then the CVV will never be
stored against the token.

The transaction response (or notification request for Sage Pay Server) will provide
the generated token. This is accessed using:

* `$response->getToken()` or
* `$response->getCardReference()`

These are equivalent since there is no difference in the way tokens or cardRererences
are generated.

## Using a Token or CardRererence

To use a token with Sage Pay Direct, you must leave the credit card details blank in
the `CreditCard` object. Sage Pay Server does not use the credit card details anyway.
To use the token as a single-use token, add it to the transaction request like this:

`request->setToken($saved_token);`

Once authorised, this token will be deleted by the gateway and so cannot be used again.
Note that if the transaction is not authorised, then the token will remain.
You should then delete the token explicitly to make sure it does not remain in the gateway
(it will sit there until the card expires, maybe for several years).

To use the token as a permanent cardReference, add it to the transaction request like this:

`request->setCardReference($saved_token);`

This CardReference will remain active on the gateway whether this transaction is authorised
or not, so can be used multiple times.

# Basket format

Sagepay currently supports two different formats for sending cart/item information to them:  
 - [BasketXML](http://www.sagepay.co.uk/support/12/36/protocol-3-00-basket-xml)
 - [Basket](http://www.sagepay.co.uk/support/error-codes/3021-invalid-basket-format-invalid)

These are incompatible with each other, and cannot be both sent in the same transaction.
*BasketXML* is the most recent format, and is the default used by this driver.
*Basket* is an older format which may be deprecated one day,
but is also the only format currently supported by some of the Sage accounting products
(e.g. Line 50) which can pull transaction data directly from Sage Pay.
For applications that require this type of integration, an optional parameter `useOldBasketFormat`
with a value of `true` can be passed in the driver's `initialize()` method.

## Sage 50 Accounts Software Integration

 The Basket format can be used for Sage 50 Accounts Software Integration:
 > It is possible to integrate your Sage Pay account with Sage Accounting products to ensure you can
> reconcile the transactions on your account within your financial software.
> If you wish to link a transaction to a specific product record this can be done through the Basket field
  in the transaction registration post.
> Please note the following integration is not currently available when using BasketXML fields. 
> In order for the download of transactions to affect a product record the first entry in a basket line needs
  to be the product code of the item within square brackets. For example:
  
```
4:[PR001]Pioneer NSDV99 DVD-Surround Sound System:1:424.68:74.32:499.00:499.00
```
 You can either prepend this onto the description or using `\Omnipay\SagePay\Extend\Item` you can use `setProductCode`
which will take care of pre-pending `[]` for you. 


# Account Types

Your Sage Pay account will use separate merchant accounts for difference transaction sources.
The sources are specified by the `accountType` parameter, and take one of three values:

* "E" Omnipay\SagePay\Message\AbstractRequest::ACCOUNT_TYPE_E (default)  
  For ecommerce transactions, entered in your application by the end user.
* "M" Omnipay\SagePay\Message\AbstractRequest::ACCOUNT_TYPE_M  
  MOTO transactions taken by telephone or postal forms or faxes, entered by an operator.
  The operator may ask for a CVV when taking a telephone order.
* "C" Omnipay\SagePay\Message\AbstractRequest::ACCOUNT_TYPE_C  
  For repeat transactions, generated by the merchant site without any human intervention.

The "M" MOTO and "C" account types will also disable any 3D-Secure validation that may
otherwise be triggered. The "C" account type will disable any CVV requirement.

The "account type" is common across other gateways, but often with different names.
Authorize.Net calls it the "business model" and includes "retail" as an option, linking
to card machines and hand-held scanners. This is not yet standardised in Omnipay, but
there are some moves to do so.

# VAT

If you want to include VAT amount in the item array you must use `\Omnipay\SagePay\Extend\Item` as follows.

```php
$items = array(
    array(new \Omnipay\SagePay\Extend\Item(array(
        'name' => 'My Product Name',
        'description' => 'My Product Description',
        'quantity' => 1,
        'price' => 9.99,
        'vat' => 1.665, // VAT amount, not percentage
    ))
);
```

# Sage Pay Server Notification Handler

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

$transactionId = time(); // Your transaction id

$items = array(
    array(
        'name' => 'My Product Name',
        'description' => 'My Product Description',
        'quantity' => 1,
        'price' => 9.99,
    )
);

$response = $gateway->purchase(array(
    'amount' => 9.99,
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
$gateway->setTestMode(true); // To access your test account.
$request = $gateway->acceptNotification();
```

Your original `transactionId` is available to look up the transaction in the database:

```php
// Use this transaction ID to look up the `$transactionReference` you saved:
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
// be needed if you want to capture the payment (for an authorize) or void or refund or
// repeat the payment later.
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

# Support

If you are having general issues with Omnipay, we suggest posting on
[Stack Overflow](http://stackoverflow.com/). Be sure to add the
[omnipay tag](http://stackoverflow.com/questions/tagged/omnipay) so it can be easily found.

If you want to keep up to date with release anouncements, discuss ideas for the project,
or ask more detailed questions, there is also a [mailing list](https://groups.google.com/forum/#!forum/omnipay) which
you can subscribe to.

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/thephpleague/omnipay-sagepay/issues),
or better yet, fork the library and submit a pull request.
