<?php
/*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright � 2016-present heidelpay GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Ronja Wann
* @category JTL
*/

defined('DD_MAIL_SUBJECT') or define('DD_MAIL_SUBJECT','Zahlungsinformation zu Ihrer Bestellung bei {COMPANY_NAME}');
defined('DD_MAIL_TEXT') or define('DD_MAIL_TEXT','
<html><body><font face="helvetica">
    Der Betrag von <strong>{AMOUNT} {CURRENCY}</strong> wird in den nächsten Tagen von Ihrem Konto abgebucht.<br/><br/>
    <br/>
    Die Abbuchung enthält die Mandatsreferenz-ID: <strong>{ACC_IDENT}</strong><br/>
    und die Gläubiger ID: <strong>{IDENT_CREDITOR}</strong><br/>
    <br/><i>Bitte sorgen Sie für ausreichende Deckung auf dem entsprechenden Konto.</i>
    <br/><br/>
    Mit freundlichem Gruß<br />
    Ihr Team von {COMPANY_NAME}
</font></body></html>
');

defined('PP_MAIL_SUBJECT') or define('PP_MAIL_SUBJECT','Zahlungsinformation zu Ihrer Bestellung bei {COMPANY_NAME}');
defined('PP_MAIL_TEXT') or define('PP_MAIL_TEXT','
<html><body><font face="helvetica">
    Bitte überweisen Sie uns den Betrag von <strong>{AMOUNT} {CURRENCY}</strong> auf folgendes Konto:<br/><br/>

    Kontoinhaber: 	{ACC_OWNER}<br/>
    IBAN: 			{ACC_IBAN}<br/>
    BIC: 				{ACC_BIC}<br/><br/>
    <i>Geben Sie als Verwendungszweck bitte ausschließlich diese Identifikationsnummer an:</i><br/>
    <strong>{USAGE}</strong><br/><br/>
    <br/><br/>
    Mit freundlichen Grüßen,<br />
    Ihr Team von {COMPANY_NAME}
</font></body></html>
');

defined('IV_PAY_INFO') or define('IV_PAY_INFO','
    Bitte überweisen Sie uns den Betrag von {PRESENTATION_AMOUNT}
    {PRESENTATION_CURRENCY} nach Erhalt der Ware auf folgendes Konto:

    Kontoinhaber: {ACCOUNT_HOLDER}
    IBAN: {ACCOUNT_IBAN}
    BIC: {ACCOUNT_BIC}

    Geben Sie als Verwendungszweck bitte ausschließlich folgende Identifikationsnummer an:
    {SHORTID}
');