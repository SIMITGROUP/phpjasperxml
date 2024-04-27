# tc-lib-pdf-encrypt
*PHP library to encrypt data for PDF*

[![Latest Stable Version](https://poser.pugx.org/tecnickcom/tc-lib-pdf-encrypt/version)](https://packagist.org/packages/tecnickcom/tc-lib-pdf-encrypt)
![Build](https://github.com/tecnickcom/tc-lib-pdf-encrypt/actions/workflows/check.yml/badge.svg)
[![Coverage](https://codecov.io/gh/tecnickcom/tc-lib-pdf-encrypt/graph/badge.svg?token=Pv1MNH3X3v)](https://codecov.io/gh/tecnickcom/tc-lib-pdf-encrypt)
[![License](https://poser.pugx.org/tecnickcom/tc-lib-pdf-encrypt/license)](https://packagist.org/packages/tecnickcom/tc-lib-pdf-encrypt)
[![Downloads](https://poser.pugx.org/tecnickcom/tc-lib-pdf-encrypt/downloads)](https://packagist.org/packages/tecnickcom/tc-lib-pdf-encrypt)

[![Donate via PayPal](https://img.shields.io/badge/donate-paypal-87ceeb.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&currency_code=GBP&business=paypal@tecnick.com&item_name=donation%20for%20tc-lib-pdf-encrypt%20project)
*Please consider supporting this project by making a donation via [PayPal](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&currency_code=GBP&business=paypal@tecnick.com&item_name=donation%20for%20tc-lib-pdf-encrypt%20project)*

* **category**    Library
* **package**     \Com\Tecnick\Pdf\Encrypt
* **author**      Nicola Asuni <info@tecnick.com>
* **copyright**   2011-2024 Nicola Asuni - Tecnick.com LTD
* **license**     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
* **link**        https://github.com/tecnickcom/tc-lib-pdf-encrypt
* **SRC DOC**     https://tcpdf.org/docs/srcdoc/tc-lib-pdf-encrypt

## Description

PHP library to encrypt data for PDF.

The initial source code has been derived from [TCPDF](<http://www.tcpdf.org>).


### OpenSSL 3

When using OpenSSL 3 some legacy providers are disabled by default.
This will trigger the following error when executing the test:

```
error:0308010C:digital envelope routines::unsupported
```

Legacy provider can be enabled by followign the instructions at: https://wiki.openssl.org/index.php/OpenSSL_3.0#Providers

```bash
sudo nano /etc/ssl/openssl.cnf
```

```bash
   openssl_conf = openssl_init

   [openssl_init]
   providers = provider_sect
   
   [provider_sect]
   default = default_sect
   legacy = legacy_sect
   
   [default_sect]
   activate = 1
   
   [legacy_sect]
   activate = 1
```

An alternative OpenSSL configuration file is provided on and set by default in the Makefile.
To overrride the custom OpenSSL configuration file set the following environment variable:

```bash
export OPENSSL_CONF=/etc/ssl/openssl.cnf
```

## Getting started

This library requires the openssl php extension.

First, you need to install all development dependencies using [Composer](https://getcomposer.org/):

```bash
$ curl -sS https://getcomposer.org/installer | php
$ mv composer.phar /usr/local/bin/composer
```

This project include a Makefile that allows you to test and build the project with simple commands.
To see all available options:

```bash
make help
```

To install all the development dependencies:

```bash
make deps
```

## Running all tests

Before committing the code, please check if it passes all tests using

```bash
make qa
```

All artifacts are generated in the target directory.


## Example

Examples are located in the `example` directory.

Start a development server (requires PHP 8.0+) using the command:

```
make server
```

and point your browser to <http://localhost:8000/index.php>


## Installation

Create a composer.json in your projects root-directory:

```json
{
    "require": {
        "tecnickcom/tc-lib-pdf-encrypt": "^2.0"
    }
}
```

Or add to an existing project with: 

```bash
composer require tecnickcom/tc-lib-pdf-encrypt ^2.0
```

## Packaging

This library is mainly intended to be used and included in other PHP projects using Composer.
However, since some production environments dictates the installation of any application as RPM or DEB packages,
this library includes make targets for building these packages (`make rpm` and `make deb`).
The packages are generated under the `target` directory.

When this library is installed using an RPM or DEB package, you can use it your code by including the autoloader:
```
require_once ('/usr/share/php/Com/Tecnick/Pdf/Encrypt/autoload.php');
```



## Developer(s) Contact

* Nicola Asuni <info@tecnick.com>
