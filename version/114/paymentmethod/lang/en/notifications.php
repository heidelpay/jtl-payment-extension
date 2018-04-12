<?php
/*
 * en language file for notifications.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
*/

define('DD_MAIL_SUBJECT','Additional information about your order at '.$firma->cName);
define('DD_MAIL_TEXT','
<html><body><font face="helvetica">
    The amount of <strong>{AMOUNT} {CURRENCY}</strong> will be debited from this account within the next days:<br/><br/>
    Holder: {HOLDER}<br/>
    IBAN: {ACC_IBAN}<br/>
    BIC: {ACC_BIC}<br/>
    The booking contains the mandate reference ID: {ACC_IDENT}<br/>
    and the creditor identifier: {IDENT_CREDITOR}<br/>
    <br/>Please ensure that there will be sufficient funds on the corresponding account.
    <br/><br/>
    Kind regards,<br />
    your team from {COMPANY_NAME}
</font></body></html>
');

defined('PP_MAIL_SUBJECT') or define('PP_MAIL_SUBJECT','Payment information for your order at '.$firma->cName);
defined('PP_MAIL_TEXT') or define('PP_MAIL_TEXT','
<html><body><font face="helvetica">
    Please transfer the amount of <strong>{AMOUNT} {CURRENCY}</strong> to the following account:<br/><br/>

    Holder: 	{ACC_OWNER}<br/>
    IBAN: 			{ACC_IBAN}<br/>
    BIC: 				{ACC_BIC}<br/><br/>
    <i>Please use only the following identification number as descriptor:</i><br/>
    <strong>{USAGE}</strong><br/><br/>
    <br/><br/>
    With kind regards,<br />
    your team from {COMPANY_NAME}
</font></body></html>
');

defined('IV_PAY_INFO') or define('IV_PAY_INFO','
    Please transfere the ammount of {PRESENTATION_AMOUNT}
    {PRESENTATION_CURRENCY} after receive of the goods:

    Account holder: {ACCOUNT_HOLDER}
    IBAN: {ACCOUNT_IBAN}
    BIC: {ACCOUNT_BIC}

    Only use the following identification number as reference please:
    {SHORTID}
');