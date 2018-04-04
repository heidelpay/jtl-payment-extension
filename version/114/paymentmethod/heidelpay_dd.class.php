<?php
/*
 * SUMMARY
 *
 * DESC
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/' . $oPlugin->nVersion . '/paymentmethod/heidelpay_standard.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods\DirectDebitPaymentMethod;

class heidelpay_dd extends heidelpay_standard
{

    public function setPaymentObject()
    {
        $this->paymentObject = new DirectDebitPaymentMethod();
    }

    public function sendPaymentRequest()
    {
        $this->paymentObject->debit();
    }

    public function sendPaymentMail(Bestellung $order, $args)
    {
        $firma = Shop::DB()->query("SELECT * FROM tfirma", 1);
        $repl = array(
            '{ACC_IBAN}' => $args ['ACCOUNT_IBAN'],
            '{ACC_BIC}' => $args ['ACCOUNT_BIC'],
            '{ACC_IDENT}' => $args ['ACCOUNT_IDENTIFICATION'],
            '{AMOUNT}' => $args ['PRESENTATION_AMOUNT'],
            '{CURRENCY}' => $args ['PRESENTATION_CURRENCY'],
            '{HOLDER}' => $args ['ACCOUNT_HOLDER'],
            '{COMPANY_NAME}' => $firma->cName
        );
        if (isset($args ['IDENTIFICATION_CREDITOR_ID']) && ($args ['IDENTIFICATION_CREDITOR_ID'] != '')) {
            $repl ['{IDENT_CREDITOR}'] = $args ['IDENTIFICATION_CREDITOR_ID'];
        } else {
            $repl ['{IDENT_CREDITOR}'] = '-';
        }

        $subject = strtr(constant('DD_MAIL_SUBJECT'), $repl);
        $mail_text = strtr(constant('DD_MAIL_TEXT'), $repl);

        mail(
            $order->oRechnungsadresse->cMail,
            $subject,
            $mail_text,
            constant('DD_MAIL_HEADERS')
        );
    }
}
