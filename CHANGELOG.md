# Release Notes - heidelpay extension for JTL Shop

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