<?php

/* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Ronja Wann
* @category JTL
*/

$headers = 'From: '.$einstellungen['emails']['email_master_absender_name'].' <'.$einstellungen['emails']['email_master_absender'].'>'. "\r\n";
$headers .= 'Reply-To: '.$einstellungen['emails']['email_master_absender']. "\r\n";
$headers .= 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

define('DD_MAIL_HEADERS', $headers);
define('DD_MAIL_SUBJECT','Zahlungsinformation zu Ihrer Bestellung bei '.$firma->cName);
define('DD_MAIL_TEXT','
	<html><body><font face="helvetica">
		Der Betrag von <strong>{AMOUNT} {CURRENCY}</strong> wird in den nächsten Tagen von folgendem Konto abgebucht:<br/><br/>
		Kontoinhaber: {HOLDER}<br/>
		IBAN: {ACC_IBAN}<br/>
		BIC: {ACC_BIC}<br/>
		<br/>
		Die Abbuchung enthält die Mandatsreferenz-ID: <strong>{ACC_IDENT}</strong><br/>
		und die Gläubiger ID: <strong>{IDENT_CREDITOR}</strong><br/>
		<br/><i>Bitte sorgen Sie für ausreichende Deckung auf dem entsprechenden Konto.</i>
		<br/><br/>
		Mit freundlichem Gruß<br />
		Ihr Team von '.$firma->cName.'
	</font></body></html>
');
?>