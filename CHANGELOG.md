# Release Notes - heidelpay extension for JTL Shop

##115
### 19.01.21
#### Changed
- Compatibility changes in order to the Jtl certification process.
- Email templates editable in shop backend.
- css file can be used to adjust style of iFrame.
- Licence file is saved in file format md will be shown before installation.
- It will be checked beforehand whether customer is 18 years old allready.


##114

### 18.08.29
#### Fixed
- spaces in front or after payment credentials, caused the transaction to fail.
- Secured invoice was not disabled after customer was denied by insurance provider.
- Error mail was sent when automatic finalize failed because Reservation was already
finalized.
- function getCurrentVersion used to set the SHOPMODULE.VERSION can
cause an exception

#### Changed
- iFrame language can now be different from "EN" and "DE" and therefore also allows 
other languages for the iFrame.
- allow installation in directory name other than "heidelpay_standard"

### 18.06.26

#### Added
- basket for secured invoice and secured direct debit to increase the acceptance rate of the insurance provider.
- info about the used shop and plugin version are in the payment request.
- support for asynchronously incoming payments via push notification.
- send uniqueId to WAWI for an easier way to map the payment transactions.

#### Changed
- secured invoice is no longer available for the time of the actual session after user was denied by insurance provider.
    That behaviour should avoid repetitive payment requests which will fail.
- "sofortüberweisung" was renamed "Sofort". (Reconfiguration necessary)
- Heidelberger Payment GmbH was renamed heidelpay GmbH.

####Fixed
- added Switch for secured direct debit to choose between booking modes authorize and debit(default).

## v113

###17.12.01

- added version 113
- added xmlQuery Class
- added arrayFilter Class
- added hook181 for auto finalize
- added field for error reporting address in merchant backend

####Changed
- disabled Santander invoice in info.xml
- replaced heidelpay/php-api with heidelpay/php-payment-api 
- replaced heidelpay/php-customer-messages with heidelpay/php-message-code-mapper
- changed namespaces according to new packages 
- modified getIp() for proxy cases

## v112

###17.09.04

####Fixed
- remove setlocal
- add stdclass to handNotification
- remove unused heidelpay_standard class call from hook 140

####Changed
- add getErrorReturnURL method to heidelpay_standard class

###17.08.31:

####Fixed
- optimized javascript for safari


###17.07.19:

####Changed

- added function getIP()
- updated php-api files

###17.07.13:

####Fixed
- hotfix prepayment and inovice response flow

###17.06.12:

####Fixed
- problems with umlauts in case of secured invoice

####Changed
- months will now be displayed in numbers in date picker for secured invoice and direct debit 


###17.05.22:

####Fixed
- paid status in JTL commodities management system 

Note: Creditcard/Debitcard Reservations will be shown as paid to the customer 
but not in the CMS since the amount isn't captured with the reservation yet



###17.05.18:

Subversion with Bugfix for Sofort Banking 
#### Fixed
- blank page at sofort banking if pay type is set to 'pay before end of order' doesn't appear anymore

#### Removed
- unused files 

###17.05.03:

JTL plugin version 112 introduces payment via white label gateway. 
All former payment methods were moved to white label and new payment methods were added.

#### Added
- version folder 112
- new payment method Przelewy24
- new payment method Postfinance eFinance
- new payment method Postfinance Card
- new payment method Santander invoice
- new payment method invoice secured
- new payment method direct debit secured

#### Changed
- updated version to 112
- existing payment methods now running via white label gateway 

#### Fixed
- notice 'not supported for JTL4' doesn't appear anymore 

#### Removed
- sql folder
- deprecated payment method Billsafe
- unused code 