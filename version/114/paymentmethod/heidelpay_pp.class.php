<?php
/*
 * Prepayment card paymentmethod
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once $oPlugin->cPluginPfad . 'paymentmethod/heidelpay_standard.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods\PrepaymentPaymentMethod;

class heidelpay_pp extends heidelpay_standard
{

    public function setPaymentObject()
    {
        $this->paymentObject = new PrepaymentPaymentMethod();
    }

    public function sendPaymentMail(Bestellung $order, $args)
    {
        $firma = Shop::DB()->query("SELECT * FROM tfirma", 1);
        $repl = array(
            '{ACC_IBAN}' => $args ['CONNECTOR_ACCOUNT_IBAN'],
            '{ACC_BIC}' => $args ['CONNECTOR_ACCOUNT_BIC'],
            '{ACC_OWNER}' => $args ['CONNECTOR_ACCOUNT_HOLDER'],
            '{AMOUNT}' => $args ['PRESENTATION_AMOUNT'],
            '{CURRENCY}' => $args ['PRESENTATION_CURRENCY'],
            '{USAGE}' => $args ['IDENTIFICATION_SHORTID'],
            '{COMPANY_NAME}' => $firma->cName
        );
        $mailer = new SimpleMail();
        $address = [
            [
                'cMail' => $order->oRechnungsadresse->cMail,
                'cName' => $order->oRechnungsadresse->cVorname . ' ' . $order->oRechnungsadresse->cNachname
            ]
        ];
        $subject = strtr(constant('PP_MAIL_SUBJECT'), $repl);
        $mail_text = strtr(constant('PP_MAIL_TEXT'), $repl);

        $mailer->setBetreff($subject);
        $mailer->setBodyHTML($mail_text);
        $mailer->send($address);
    }
}
