![Logo](https://dev.heidelpay.de/devHeidelpay_400_180.jpg)

# jtl-payment-extension
Payment extension for JTL Shop

## NOTICE
This extension is currently under development, please use the current extension version 111 from https://dev.heidelpay.de/jtl

## Currently supported payment methods:

* credit card
* debit card
* prepayment
* Sofort
* PayPal
* direct debit
* iDeal
* Giropay
* Przelewy24
* PostFinance Card
* PostFinance EFinance
* EPS
* invoice
* invoice secured b2c
* direct debit secured b2c
* Santander invoice

### SYSTEM REQUIREMENTS

JTL payment extension requires PHP 5.6 or higher; we recommend using the
latest stable PHP version whenever possible.

## SECURITY ADVICE
If you want to store the output of this library into a database or something, please make sure that your
application takes care of sql injection, cross-site-scripting (xss) and so on. There is no build in protection
by now.

## LICENSE

You can find a copy of this license in [LICENSE.txt](LICENSE.txt).



