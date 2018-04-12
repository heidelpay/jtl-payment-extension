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
require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/' .$oPlugin->nVersion. '/paymentmethod/heidelpay_standard.class.php';

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
        mail(
            $order->oRechnungsadresse->cMail,
            strtr(constant('PP_MAIL_SUBJECT'), $repl),
            strtr(constant('PP_MAIL_TEXT'), $repl),
            $this->getMailHeader()
        );
    }
}
