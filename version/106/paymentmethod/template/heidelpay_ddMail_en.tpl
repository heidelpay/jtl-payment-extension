<?php
$headers = 'From: '.$einstellungen['emails']['email_master_absender_name'].' <'.$einstellungen['emails']['email_master_absender'].'>'. "\r\n";
$headers .= 'Reply-To: '.$einstellungen['emails']['email_master_absender']. "\r\n";
$headers .= 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

define('DD_MAIL_HEADERS', $headers);
define('DD_MAIL_SUBJECT','Additional info about your order at '.$firma->cName);
define('DD_MAIL_TEXT','
	<html><body>
		The amount will be debited from this account within the next days:<br/><br/>		
		IBAN: {ACC_IBAN}<br/>
		BIC: {ACC_BIC}<br/>
		The booking contains the mandate reference ID: {ACC_IDENT}<br/>
		and the creditor identifier: {IDENT_CREDITOR}<br/>
		<br/>Please ensure that there will be sufficient funds on the corresponding account.
		<br/><br/>
		Kind regards,<br />
		your team from '.$firma->cName.'
	</body></html>
');
?>