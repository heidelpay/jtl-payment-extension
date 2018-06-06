
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


/**
 * Fixing update to 114
 */

/* Find Plugin with old name */
SET @old_plugin_name = (SELECT `cModulId`
                        FROM `tzahlungsart`
                        WHERE `cModulId`
                              LIKE 'kPlugin\_%\_heidelpaysofort%berweisungplugin');

/* Identify the plugin number */
SET @number_start = LOCATE('_', @old_plugin_name) + 1;
SET @number_end = LOCATE('_', @old_plugin_name, @number_start + 1) - 1;
SET @plugin_number = SUBSTR(@old_plugin_name, @number_start, @number_end - @number_start + 1);

/* If no Sofortüberweisung exist */
SET @new_plugin_name = if(
    ISNULL(@old_plugin_name),
    (
      SELECT `cModulId`
      FROM `tzahlungsart`
      WHERE `cModulId`
            LIKE 'kPlugin\_%\_heidelpaysofortplugin'),
    CONCAT('kPlugin_', @plugin_number, '_heidelpaysofortplugin'
    )
);

/* Set the correct cModulId for Sofort and deactivate Sofortüberweisung*/
UPDATE `tzahlungsart` SET `cModulId` = @new_plugin_name WHERE `cModulId` LIKE 'kPlugin\__%\_heidelpaysofortplugin';
UPDATE `tzahlungsart` SET `nActive` = 0 WHERE `cModulId` LIKE 'kPlugin\_%\_heidelpaysofort%berweisungplugin';

