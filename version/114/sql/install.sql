
CREATE TABLE `xplugin_heidelpay_standard_push_notification` (
  `kpush` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `transaction_id` VARCHAR( 255 ) NOT NULL ,
  `unique_id` VARCHAR( 255 ) NOT NULL UNIQUE,
  `reference_id` VARCHAR( 255 ),
  `timestamp` DATETIME NOT NULL
) ENGINE = MYISAM ;