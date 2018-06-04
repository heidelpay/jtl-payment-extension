
CREATE TABLE `xplugin_heidelpay_standard_push_notification` (
  `kpush` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `transaction_id` VARCHAR( 255 ) NOT NULL ,
  `unique_id` VARCHAR( 255 ) NOT NULL UNIQUE,
  `reference_id` VARCHAR( 255 ),
  `timestamp` DATETIME NOT NULL
) ENGINE = INNODB ;

CREATE TABLE `xplugin_heidelpay_standard_order_reference` (
  `korder_reference` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  /*`kBestellung` BIGINT NOT NULL ,*/
  `cBestellNr` VARCHAR( 20 ) NOT NULL,
  `cTempBestellNr` VARCHAR( 20 ) NOT NULL
) ENGINE = INNODB ;

CREATE TABLE `xplugin_heidelpay_standard_finalize` (
  `kfinalize` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `cshort_id` CHAR( 14 ) NOT NULL,
  `kBestellung` VARCHAR( 20 ) NOT NULL
) ENGINE = INNODB ;
