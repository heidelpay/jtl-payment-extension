<?php

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

$headers = 'From: '.$einstellungen['emails']['email_master_absender_name'].' <'.$einstellungen['emails']['email_master_absender'].'>'. "\r\n";
$headers .= 'Reply-To: '.$einstellungen['emails']['email_master_absender']. "\r\n";
$headers .= 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

define('DD_MAIL_HEADERS', $headers);
define('DD_MAIL_SUBJECT','Zusatzinfo zu Ihrer Bestellung bei '.$firma->cName);
define('DD_MAIL_TEXT','
	<html><body>
		Der Betrag wird in den nächsten Tagen von folgendem Konto abgebucht:<br/><br/>	
		IBAN: {ACC_IBAN}<br/>
		BIC: {ACC_BIC}<br/>
		Die Abbuchung enthält die Mandatsreferenz-ID: {ACC_IDENT}<br/>
		und die Gläubiger ID: {IDENT_CREDITOR}<br/>
		<br/>Bitte sorgen Sie für ausreichende Deckung auf dem entsprechenden Konto.
		<br/><br/>
		Mit freundlichem Gruß<br />
		Ihr Team von '.$firma->cName.'
	</body></html>
');
?>