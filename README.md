# Omnipay: Sage Pay

**Sage Pay driver for the Omnipay PHP payment processing library**

[![Build Status](https://travis-ci.org/thephpleague/omnipay-sagepay.png?branch=master)](https://travis-ci.org/thephpleague/omnipay-sagepay)
[![Latest Stable Version](https://poser.pugx.org/omnipay/sagepay/version.png)](https://packagist.org/packages/omnipay/sagepay)
[![Total Downloads](https://poser.pugx.org/omnipay/sagepay/d/total.png)](https://packagist.org/packages/omnipay/sagepay)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic,
multi-gateway payment processing library for PHP.
This package implements Sage Pay support for Omnipay.
This version supports PHP ^5.6 and PHP ^7.

This is the `master` branch of Omnipay, handling Omnipay version `3.x`.
For the `2.x` branch, please visit https://github.com/thephpleague/omnipay-sagepay/tree/2.x

Table of Contents
=================

   * [Omnipay: Sage Pay](#omnipay-sage-pay)
   * [Table of Contents](#table-of-contents)
   * [Installation](#installation)
   * [Basic Usage](#basic-usage)
   * [Supported Methods](#supported-methods)
      * [Sage Pay Direct Methods](#sage-pay-direct-methods)
         * [Direct Authorize/Purchase](#direct-authorizepurchase)
            * [Redirect (3D Secure)](#redirect-3d-secure)
            * [Redirect Return](#redirect-return)
         * [Direct Create Card](#direct-create-card)
      * [Sage Pay Server Methods](#sage-pay-server-methods)
         * [Server Gateway](#server-gateway)
         * [Server Authorize/Purchase](#server-authorizepurchase)
         * [Server Create Card](#server-create-card)
         * [Server Notification Handler](#server-notification-handler)
      * [Sage Pay Form Methods](#sage-pay-form-methods)
         * [Form Authorize](#form-authorize)
         * [Form completeAuthorize](#form-completeauthorize)
         * [Form Purchase](#form-purchase)
      * [Sage Pay Shared Methods (Direct and Server)](#sage-pay-shared-methods-direct-and-server)
         * [Repeat Authorize/Purchase](#repeat-authorizepurchase)
         * [Capture](#capture)
         * [Delete Card](#delete-card)
   * [Token Billing](#token-billing)
      * [Generating a Token or CardReference](#generating-a-token-or-cardreference)
      * [Using a Token or CardReference](#using-a-token-or-cardreference)
   * [Basket format](#basket-format)
      * [Sage 50 Accounts Software Integration](#sage-50-accounts-software-integration)
   * [Account Types](#account-types)
   * [VAT](#vat)
   * [Support](#support)

# Installation

Omnipay is installed via [Composer](http://getcomposer.org/).
To install, simply add it to your `composer.json` file:

```json
{
    "require": {
        "omnipay/sagepay": "~3.0"
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
* SagePay_Form

For general Omnipay usage instructions, please see the main
[Omnipay](https://github.com/thephpleague/omnipay) repository.

# Supported Methods

## Sage Pay Direct Methods

Sage Pay Direct is a server-to-server protocol, with all credit card details
needing to pass through your application for forwarding on to the gateway.
You must be aware of the PCI implications of handling credit card details
if using this API.

The Direct gateway methods for handling cards are:

* `authorize()` - with completeAuthorize for 3D Secure and PayPal redirect
* `purchase()` - with completePurchase for 3D Secure and PayPal redirect
* `createCard()` - explicit "standalone" creation of a cardReference or token

*Note: PayPal is not yet implemented in this driver.*

### Direct Authorize/Purchase

```php
use Omnipay\Omnipay;
use Omnipay\Common\CreditCard;

// Create the gateway object.

$gateway = OmniPay::create('SagePay\Direct')->initialize([
    'vendor' => 'vendorname',
    'testMode' => true,
]);

// Create the credit card object from details entered by the user.

$card = new CreditCard([
    'firstName' => 'Card',
    'lastName' => 'User',

    'number' => '4929000000006',
    'expiryMonth' => '12',
    'expiryYear' => '2019',
    'CVV' => '123',

    // Billing address details are required.
    ...
]);

// Create the minimal request message.

$requestMessage = $gateway->purchase([
    'amount' => '99.99',
    'currency' => 'GBP',
    'card' => $card,
    'transactionId' => $transactionId,
    'description' => 'Pizzas for everyone at PHPNE',

    // If 3D Secure is enabled, then provide a return URL for
    // when the user comes back from 3D Secure authentication.

    'returnUrl' => 'https://example.co.uk/sagepay-complete',
]);

// Send the request message.

$responseMessage = $requestMessage->send();
```

At this point you will have either a final result or a redirect.

If `$responseMessage->isSuccessful()` is `true`, then the authorization is
complete and successful. If `false` then check for a redirect, otherwise
the authorization was not successful.

#### Redirect (3D Secure)

If the authorization result is a redirect, then a quick and dirty way to redirect is:

```php
if ($responseMessage->isRedirect()) {
    $responseMessage->redirect();
}
```

That `redirect()` method is intended just for demonstration or testing.
Create your own instead, within your framework, using these helpers:

* `$responseMessage->getRedirectUrl()`
* `$responseMessage->getRedirectMethod()`
* `$responseMessage->getRedirectData()`

#### Redirect Return

After the user has performed their 3D Secure authentication, they will
be redirected (via `POST`) back to your `returnUrl` endpoint.
The transaction is not yet complete.
It must be completed like this:

```php
$completeRequest = $gateway->completeAuthorize([
    'transactionId' => $transactionId,
]);
$completeResponse = $completeRequest->send();
```

The `$transactionId` (same as created for the original `purchase()`)
is only needed if you want to save `getTransactionReference()`
for future repeat payments.

The normal getters will be available here to check the result,
get the `cardReference` for saving etc.

### Direct Create Card

This will create a card reference with no authorization.
If you want to authorize an amount on the card *and* get a cardReference
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
// card is not being authorized at this point.

if ($response->isSuccessful()) {
    $cardReference = $response->getCardReference();
    // or if you prefer to treat it as a single-use token:
    $token = $response->getToken();
}
```

## Sage Pay Server Methods

Sage Pay Server captures any credit card details in forms hosted by the
Sage Pay gateway, either by sending the user to the gateway or loading the
hosted forms in an iframe. This is the preferred and safest API to use.

Sage Pay Server uses your IP address to authenticate backend access to the
gateway, and it also needs to a public URL that it can send back-channel
notifications to. This makes development on a localhost server difficult.

* `authorize()`
* `purchase()`
* `createCard()` - explicit "standalone" creation of a cardReference or token
* `acceptNotification()` - Notification Handler for authorize, purchase and
   explicit cardReference registration

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

### Server Authorize/Purchase

This method authorizes a payment against a credit or debit card.
A `cardToken` or `cardReference` previously captured, can be used here, and only
the user's CVV will be asked for, but the overall flow will remain the same.

The `$creditCard` object will provide the billing and shipping details:

```php
use Omnipay\Common\CreditCard;

$creditCard = new CreditCard([
    'billingFirstName' => 'Joe',
    'billingLastName' => 'Bloggs',
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
    'shippingFirstName' => 'Joe',
    'shippingLastName' => 'Bloggs',
    'shippingAddress1' => '99',
    'shippingState' => 'NY',
    'shippingCity' => 'City1',
    'shippingPostcode' => 'SPOSTC',
    'shippingCountry' => 'US',
    'shippingPhone' => '01234 567 890 SS',
]);
```

* The country must be a two-character ISO 3166 code.
* The state will be a two-character ISO code, and is mandatory if the country is "US".
* The state will be ignored if the country is not "US".
* Address2 is optional, but all other fields are mandatory.
* The postcode is optional for Republic of Ireland "IE",
  though *some* banks insist it is present and valid.
* This gateway lives on an extended ASCII ISO 8859-1 back end.
  Really. Do any characterset conversions in your merchant site to avoid surprises.
* Both billing and shipping name and address is required.
  However, you can use the `billingForShipping` flag to set the shipping details
  to what you supply as the billing details.

```php
// Use the billing name and address for the shipping name and address too.
$gateway->setBillingForShipping(true);

// or

$response = $gateway->authorize([
    'billingForShipping' => true,
    ...
]);
```

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

$response = $gateway->authorize([
    'amount' => '9.99',
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
    // 'useAuthenticate' => true,
])->send();

If `useAuthenticate` is set, then the `authorize` will use the `AUTHENTICATE`/`AUTHORISE`
method of reserving the transaction details.
If `useAuthenticate` is not set (the default) then the `DEFERRED`/`RELEASE`
method of reserving the transaction details will be used.
The same method must be used when capturing the transaction.

// Create storage for this transaction now, indexed by the transaction ID.
// We will need to access it in the notification handler.
// The reference given by `$response->getTransactionReference()` must be stored.

// Now decide what to do next, based on the response.

if ($response->isSuccessful()) {
    // The transaction is complete and successful and no further action is needed.
    // This may happen if a cardReference has been supplied, having captured
    // the card reference with a CVV and using it for the first time. The CVV will
    // only be kept by the gateway for this first authorization. This also assumes
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

### Server Create Card

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

### Server Notification Handler

> **NOTE:** The notification handler was previously handled by the SagePay_Server `completeAuthorize`,
  `completePurchase` and `completeRegistration` methods.
  The notification handler replaces all of these.

The `SagePay_Server` gateway uses a notification callback to receive the results of a payment or authorization.
Sage Pay Direct does not use the notification handler.

Unlike many newer gateways, this notification handler is not just an optional callback
providing an additional channel for events.
It is *required* for the Server gateway, and not used for the direct gateway at all.

The URL for the notification handler is set in the authorize or payment message:

```php
// The Server response will be a redirect to the Sage Pay CC form.
// This is a Sage Pay Server Purchase request.

$transactionId = {create a unique transaction id};

$items = [
    [
        'name' => 'My Product Name',
        'description' => 'My Product Description',
        'quantity' => 1,
        'price' => 9.99,
    ]
];

$response = $gateway->purchase([
    'amount' => 9.99,
    'currency' => 'GBP',
    // Just the name and address, NOT CC details.
    'card' => $card,
    // The route to your application's notification handler.
    'notifyUrl' => 'https://example.com/notify',
    'transactionId' => $transactionId,
    'description' => 'test',
    'items' => $items,
])->send();

// Before redirecting, save `$response->getSecurityKey()` in the database,
// retrievable by `$transactionId`.

if ($response->isRedirect()) {
    // Go to Sage Pay to enter CC details.
    // While your user is there, the notification handler will be called
    // to accept the result and provide the final URL for the user.

    $response->redirect();
}
```

Your notification handler needs to do four things:

1. Look up the saved transaction in the database to retrieve the `securityKey`.
2. Validate the signature of the received notification to protect against tampering.
3. Update your saved transaction with the results.
4. Respond to Sage Pay to indicate that you accept the result, reject the result or don't
   believe the notification was valid.
   Also tell Sage Pay where to send the user next.

This is a back-channel (server-to-server), so has no access to the end user's session.

The acceptNotification gateway is set up simply.
The `$request` will capture the POST data sent by Sage Pay:

```php
$gateway = Omnipay\Omnipay::create('SagePay_Server');
$gateway->setVendor('your-vendor-name');
$gateway->setTestMode(true); // To access your test account.
$notifyRequest = $gateway->acceptNotification();
```

Your original `transactionId` is available to look up the transaction in the database:

```php
// Use this transaction ID to look up the `$securityKey` you saved:

$transactionId = $notifyRequest->getTransactionId();
$transaction = customFetchMyTransaction($transactionId); // Local storage
$securityKey = $transaction->getSecurityKey(); // From your local storage

// Alternatively, if you did not save the `securityKey` as a distinct field,
// then use the `transactionReference` you saved.
// The `transactionReference` for this driver will be a compound JSON string
// with the `securityKey` as an integral part of it, so the driver can use it
// directly.

$transactionReference = $transaction->getTransactionReference(); // From your local storage
```

Now the signature can be checked:

```php
$notifyRequest->setSecurityKey($securityKey);
// or
$notifyRequest->setTransactionReference($transactionReference);

if (! $notifyRequest->isValid()) {
    // Respond to Sage Pay indicating we are not accepting anything about this message.
    // You might want to log `$request->getData()` first, for later analysis.

    $notifyRequest->invalid($nextUrl, 'Signature not valid - goodbye');
}
```

If you were not able to look up the transaction or the transaction is in the wrong state,
then indicate this with an error. Note an "error" is to indicate that although the notification
appears to be legitimate, you do not accept it or cannot handle it for any reason:

```php
$notifyRequest->error($nextUrl, 'This transaction does not exist on the system');
```

> **Note:** it has been observed that the same notification message may be sent
  by Sage Pay multiple times.
  If this happens, then return the same response you sent the first time.
  So if you have confirmed a successful payment, then if you get another
  identical notification for the transaction, then return `confirm()` again.

If you accept the notification, then you can update your local records and let Sage Pay know:

```php
// All raw data - just log it for later analysis:

$notifyRequest->getData();

// Save the final transactionReference against the transaction in the database. It will
// be needed if you want to capture the payment (for an authorize) or void or refund or
// repeat the payment later.

$finalTransactionReference = $notifyRequest->getTransactionReference();

// The payment or authorization result:
// Result is $notifyRequest::STATUS_COMPLETED, $notifyRequest::STATUS_PENDING
// or $notifyRequest::STATUS_FAILED

$notifyRequest->getTransactionStatus();

// If you want more detail, look at the raw data. An error message may be found in:

$notifyRequest->getMessage();

// The transaction may be the result of a `createCard()` request.
// The cardReference can be found like this:

if ($notifyRequest->getTxType() === $notifyRequest::TXTYPE_TOKEN) {
    $cardReference = $notifyRequest->getCardReference();
}

// Now let Sage Pay know you have accepted and saved the result:

$notifyRequest->confirm($nextUrl);
```

The `$nextUrl` is where you want Sage Pay to send the user to next.
It will often be the same URL whether the transaction was approved or not,
since the result will be safely saved in the database.

The `confirm()`, `error()` and `reject()` methods will all echo the expected
return payload and expect your application to return a HTTP Status `200`
without adding any further content.

These functions used to exit the
application immediately to prevent additional output being added to
the response. You can restore this functionality by setting the `exitOnResponse`
option:

```php
$gateway->setExitOnResponse(true);
// or
$notifyRequest->setExitOnResponse(true);
```

If you just want the body payload, this method will return it without
echoing it.
You must return it with a `200` HTTP Status Code:

```php
$bodyPayload = getResponseBody($status, $nextUrl, $detail = null);
```

## Sage Pay Form Methods

Sage Pay Form requires neither a server-to-server back-channel nor
IP-based security.
It does not require pre-registration of a transaction, so is ideal for
a speculative "pay now" button on a page for instant purchases of a
product or service.
Unlike `Direct` and `Server`, it does not support saved card references
or tokens.

The payment details are encrypted on the server before being sent to
the gateway from the user's browser.
The result is returned to the merchant site also through a client-side
encrypted message.

Capturing and voiding `Form` transactions is a manual process performed
in the "My Sage Pay" administration panel.

Supported functions are:

* `authorize()`
* `purchase()`

### Form Authorize

The authorization is intialized in a similar way to a `Server` payment,
but with an `encryptionKey`:

```php
$gateway = OmniPay::create('SagePay\Form')->initialize([
    'vendor' => 'vendorname',
    'testMode' => true,
    'encryptionKey' => 'abcdef1212345678',
]);
```

The `encryptionKey` is generated in "My Sage Pay" when logged in as the administrator.

Note that this gateway driver will assume all input data (names, addresses etc.)
are UTF-8 encoded.
It will then recode the data to ISO8859-1 before encrypting it for the gateway,
since the gateway strictly accepts ISO8859-1 only, regardless of what encoding is
used to submit the form from the merchant site.
If you do not want this conversion to happen, it can be disabled with this parameter:

    'disableUtf8Decode' => true,

The authorize must be given a `returnUrl` (the return URL on success, or on failure
if no separate `failureUrl` is provided).

```php
$response = $gateway->authorize([
    ...all the normal details...
    //
    'returnUrl' => 'https://example.com/success',
    'failureUrl' => 'https://example.com/failure',
]);
```

The `$response` will be a `POST` redirect, which will take the user to the gateway.
At the gateway, the user will authenticate or authorize their credit card,
perform any 3D Secure actions that may be requested, then will return to the
merchant site.

Like `Server` and `Direct`, you can use either the `DEFERRED` or the `AUTHENTICATE`
method to reserve the amount.

### Form completeAuthorize

To get the result details, the transaction is "completed" on the
user's return. This will be at your `returnUrl` endpoint:

```php
// The result will be read and decrypted from the return URL (or failure URL)
// query parameters.
// You MUST provide the original expected transactionId, which is validated
// against the transactionId provided in the server request.
// This prevents different payments getting mixed up.

$completeRequest = $gateway->completeAuthorize(['transactionId' => $originalTransactionId]);
$result = $completeRequest->send();

$result->isSuccessful();
$result->getTransactionReference();
// etc.
```

Note that if `send()` throws an exception here due to a `transactionId` mismatch,
you can still access the decryoted data that was brought back with the user as
`$completeRequest->getData()`.
You will need to log this for later analysis.

If you already have the encrypted response string, then it can be passed in.
However, you would normally leave it for the driver to read it for you from
the current server request, so the following would not normally be necessary:

    $crypt = $_GET['crypt']; // or supplied by your framework
    $result = $gateway->completeAuthorize(['crypt' => $crypt])->send();

This is handy for testing or if the current page query parameters are not
available in a particular architecture.

It is important to make sure this result is what was expected by your
merchant site.
Your transaction ID will be returned in the result and can be inspected:

    $result->getTransactionId()

You *must* make sure this transaction ID matches the one you sent
the user off with in the first place (store it in your session).
If they do no match, then you cannot trust the result, as the user
could be running two checkout flows at the same time, possibly
for wildly different amounts.

In a future release, the `completeAuthorize()` method will expect the
`transactionId` to be supplied and it must match before it will
return a success status.

### Form Purchase

This is the same as `authorize()`, but the `purchase()` request is used instead,
and the `completePurchase()` request is used to complete the transaction on return.

## Sage Pay Shared Methods (Direct and Server)

Note: these functions do not work for the `Form` API.
These actions for `Sage Pay Form` must be performed manually through the "My Sage Pay"
admin panel.

* `capture()`
* `refund()`
* `void()` - void a purchase
* `abort()` - abort an authorization before it is captured
* `repeatAuthorize()` - new authorization based on past transaction
* `repeatPurchase()` - new purchase based on past transaction
* `deleteCard()` - remove a cardReference or token from the account

### Repeat Authorize/Purchase

An authorization or purchase can be created from a past authorization or purchase.
You will need the `transactionReference` of the original transaction.
The `transactionReference` will be a JSON string containing the four pieces of
information the gateway needs to reuse the transaction.

```php
// repeatAuthorize() or repeatPurchase()

$repeatRequest = $gateway->repeatAuthorize([
    'transactionReference' => $originalTransactionReference,
    // or
    'securityKey' => $originalSecurityKey,
    'txAuthNo' => $originalTxAuthNo,
    'vpsTxId' => $originalVPSTxId(),
    'relatedTransactionId' => $originalTransactionId,
    //
    'amount' => '99.99',
    'transactionId' => $newTransactionId.'C',
    'currency' => 'GBP',
    'description' => 'Buy it again, Sam',
]);

$repeatResponse = $repeatRequest->send();

// Treat $repeatResponse like any new authorization or purchase response.
```

### Capture

If the `useAuthenticate` parameter was set when the transaction was originally
authorized, then it must be used in the capture too.

* Setting the `useAuthenticate` parameter will cause the capture to send
  an `AUTHORISE` request. You must supply an `amount`, a `description`
  and a new `transactionId` when doing this.
  You can capture multiple amounts up to 115% of the
  original `AUTHENTICATED` (with 3D Secure) or `REGISTERED` (without 3D Secure)
  amount.
* Resetting the `useAuthenticate` parameter (false, the default mode) will cause
  the capture to send a `RELEASE` request. This will release the provided amount
  (up to the original deferred amount, but no higher) that was originally `DEFERRED`.
  You can only capture a deferred payment once, then the deferred payment will be
  closed.

Examples of each:

```php
$captureRequest = $gateway->capture([
    // authenticate is not set
    'useAuthenticate' => false,
    // Provide either the original transactionReference:
    'transactionReference' => $deferredTransactionReference,
    // Or the individual items:
    'securityKey' => $savedSecurityKey(),
    'txAuthNo' => $savedTxAuthNo(),
    'vpsTxId' => $savedVPSTxId(),
    'relatedTransactionId' => $savedTransactionId,
    // Up to the original amount, one chance only.
    'amount' => '99.99',
]);
```

```php
$captureRequest = $gateway->capture([
    // authenticate is set
    'useAuthenticate' => true,
    // Provide either the original transactionReference:
    'transactionReference' => $deferredTransactionReference,
    // Or the individual items:
    'securityKey' => $savedSecurityKey(),
    'txAuthNo' => $savedTxAuthNo(),
    'vpsTxId' => $savedVPSTxId(),
    'relatedTransactionId' => $savedTransactionId,
    // Up to 115% of the original amount, in as many chunks as you like.
    'amount' => '9.99',
    // The capture becomes a transaction in its own right.
    'transactionId' => $newTransactionId,
    'currency' => 'GBP',
    'description' => 'Take staged payment number 1',
]);
```

In both cases, send the message and check the result.

```php
$captureResponse = $captureRequest->send();

if ($captureResponse->isSuccessful()) {
    // The capture was successful.
    // There will never be a redirect here.
}
```

### Delete Card

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
// authorization is being done.

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

A token can be generated explicitly, with no authorization, or it can be generated
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

## Using a Token or CardReference

To use a token with Sage Pay Direct, you must leave the credit card details blank in
the `CreditCard` object. Sage Pay Server does not use the credit card details anyway.
To use the token as a single-use token, add it to the transaction request like this:

`request->setToken($saved_token);`

Once authorized, this token will be deleted by the gateway and so cannot be used again.
Note that if the transaction is not authorized, then the token will remain.
You should then delete the token explicitly to make sure it does not remain in the gateway
(it will sit there until the card expires, maybe for several years).

To use the token as a permanent cardReference, add it to the transaction request like this:

`request->setCardReference($saved_token);`

This CardReference will remain active on the gateway whether this transaction is authorized
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
to card machines and hand-held scanners. This is not yet standardized in Omnipay, but
there are some moves to do so.

# VAT

If you want to include VAT amount in the item array you must use
`\Omnipay\SagePay\Extend\Item` as follows.

```php
$items = [
    [new \Omnipay\SagePay\Extend\Item([
        'name' => 'My Product Name',
        'description' => 'My Product Description',
        'quantity' => 1,
        'price' => 9.99,
        'vat' => 1.665, // VAT amount, not percentage
    ]]
];
```

# Support

If you are having general issues with Omnipay, we suggest posting on
[Stack Overflow](http://stackoverflow.com/). Be sure to add the
[omnipay tag](http://stackoverflow.com/questions/tagged/omnipay) so it can be easily found.

If you want to keep up to date with release announcements, discuss ideas for the project,
or ask more detailed questions, there is also a [mailing list](https://groups.google.com/forum/#!forum/omnipay) which
you can subscribe to.

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/thephpleague/omnipay-sagepay/issues),
or better yet, fork the library and submit a pull request.
