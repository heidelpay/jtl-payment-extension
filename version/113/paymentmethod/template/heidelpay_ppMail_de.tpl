<?php

/* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo/Ronja Wann
* @category JTL
*/

$headers = 'From: '.$einstellungen['emails']['email_master_absender_name'].' <'.$einstellungen['emails']['email_master_absender'].'>'. "\r\n";
$headers .= 'Reply-To: '.$einstellungen['emails']['email_master_absender']. "\r\n";
$headers .= 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

define('PP_MAIL_HEADERS', $headers);
define('PP_MAIL_SUBJECT','Zahlungsinformation zu Ihrer Bestellung bei '.$firma->cName);
define('PP_MAIL_TEXT','
	<html><body><font face="helvetica">
	Bitte &uuml;berweisen Sie uns den Betrag von <strong>{AMOUNT} {CURRENCY}</strong> auf folgendes Konto:<br/><br/>

	Kontoinhaber: 	{ACC_OWNER}<br/>
	IBAN: 			{ACC_IBAN}<br/>
	BIC: 				{ACC_BIC}<br/><br/>
	<i>Geben Sie als Verwendungszweck bitte ausschlie&szlig;lich diese Identifikationsnummer an:</i><br/>
	<strong>{USAGE}</strong><br/><br/>
		<br/><br/>
		Mit freundlichen Grüßen,<br />
		Ihr Team von '.$firma->cName.'
	</font></body></html>
');
?>