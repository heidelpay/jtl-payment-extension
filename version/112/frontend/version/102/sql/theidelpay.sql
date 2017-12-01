/* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo
* @category JTL
*/

CREATE TABLE `xplugin_heidelpay_standard_registrations` (
`userId` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`ccard` VARCHAR( 16 ) NOT NULL ,
`exp_month` VARCHAR( 2 ) NOT NULL ,
`exp_year` VARCHAR( 4 ) NOT NULL ,
`brand` VARCHAR( 20 ) NOT NULL
) ENGINE = MYISAM ;

