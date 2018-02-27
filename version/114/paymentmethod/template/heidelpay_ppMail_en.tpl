<?php

/* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo/Ronja Wann
* @category JTL
*/

$headers = 'From: '.$einstellungen['emails']['email_master_absender_name'].' <'.$einstellungen['emails']['email_master_absender'].'>'. "\r\n";
$headers .= 'Reply-To: '.$einstellungen['emails']['email_master_absender']. "\r\n";
$headers .= 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

define('PP_MAIL_HEADERS', $headers);
define('PP_MAIL_SUBJECT','Payment information for your order at '.$firma->cName);
define('PP_MAIL_TEXT','
	<html><body><font face="helvetica">
	Please transfer the amount of <strong>{AMOUNT} {CURRENCY}</strong> to the following account:<br/><br/>

	Holder: 	{ACC_OWNER}<br/>
	IBAN: 			{ACC_IBAN}<br/>
	BIC: 				{ACC_BIC}<br/><br/>
	<i>Please use only the following identification number as descriptor:</i><br/>
	<strong>{USAGE}</strong><br/><br/>
		<br/><br/>
		With kind regards,<br />
		your team from '.$firma->cName.'
	</font></body></html>
');
?>