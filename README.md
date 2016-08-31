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

* Authorize (with completeAuthorize for 3D Secure)
* Purchase (with completeAuthorize for 3D Secure)
* Capture
* Refund
* Repeat
* Void (to be implemented)

Sage Pay Server Methods:

* Authorize
* Purchase
* Notification Handler (completeAuthorize)

### Basket format

Sagepay currently supports two different formats for sending cart/item information to them:  
 - [BasketXML](http://www.sagepay.co.uk/support/12/36/protocol-3-00-basket-xml)
 - [Basket](http://www.sagepay.co.uk/support/error-codes/3021-invalid-basket-format-invalid)

These are incompatible with each other, and cannot be both sent in the same transaction. *BasketXML* is the most modern format, and is the default used by this driver. *Basket* is an older format which may be deprecated one day, but is also the only format currently supported by some of the Sage accounting products (Line 50, etc) which can pull transaction data directly from Sagepay. Therefore for users who require this type of integration, an optional parameter `useOldBasketFormat` with a value of `true` can be passed in the driver's `initialize()` method.

## Support

If you are having general issues with Omnipay, we suggest posting on
[Stack Overflow](http://stackoverflow.com/). Be sure to add the
[omnipay tag](http://stackoverflow.com/questions/tagged/omnipay) so it can be easily found.

If you want to keep up to date with release anouncements, discuss ideas for the project,
or ask more detailed questions, there is also a [mailing list](https://groups.google.com/forum/#!forum/omnipay) which
you can subscribe to.

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/thephpleague/omnipay-sagepay/issues),
or better yet, fork the library and submit a pull request.
