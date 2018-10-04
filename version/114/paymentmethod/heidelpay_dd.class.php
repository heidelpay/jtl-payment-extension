<?php
/*
 * Direct debit paymentmethod
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
        //Prepare customer object for mailObject
        $tkunde = new stdClass();
        $tkunde->cMail = $order->oRechnungsadresse->cMail;
        $tkunde->kSprache = $order->kSprache;

        $mailingObject = new stdClass();
        $mailingObject->tkunde = $tkunde;
        $mailingObject->accIdent = $args ['ACCOUNT_IDENTIFICATION'];
        $mailingObject->amount = $args ['PRESENTATION_AMOUNT'];
        $mailingObject->currency = $args ['PRESENTATION_CURRENCY'];
        $mailingObject->holder = $args ['ACCOUNT_HOLDER'];

        if (isset($args ['IDENTIFICATION_CREDITOR_ID']) && ($args ['IDENTIFICATION_CREDITOR_ID'] != '')) {
            $mailingObject->identCreditor  = $args ['IDENTIFICATION_CREDITOR_ID'];
        } else {
            $mailingObject->identCreditor  = '-';
        }



        $template = 'kPlugin_' . $this->oPlugin->kPlugin . '_dd-reminder';
        $mail = sendeMail( $template , $mailingObject);
        Jtllog::writeLog('templateId: ' . print_r($template, 1));
        Jtllog::writeLog('mail: ' . print_r($mail, 1));
    }
}
