CREATE TABLE `xplugin_heidelpay_standard_registrations` (
`userId` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`ccard` VARCHAR( 16 ) NOT NULL ,
`exp_month` VARCHAR( 2 ) NOT NULL ,
`exp_year` VARCHAR( 4 ) NOT NULL ,
`brand` VARCHAR( 20 ) NOT NULL
) ENGINE = MYISAM ;

