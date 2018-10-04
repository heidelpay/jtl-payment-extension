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
        //prepare customer object for mailobject
        $tkunde = new stdClass();
        $tkunde->cMail = $order->oRechnungsadresse->cMail;
        $tkunde->kSprache = $order->kSprache;

        $mailingObject = new stdclass();
        $mailingObject->tkunde = $tkunde;
        $mailingObject->acciban = $args ['connector_account_iban'];
        $mailingObject->accbic = $args ['connector_account_bic'];
        $mailingObject->owner = $args ['connector_account_holder'];
        $mailingObject->amount = $args ['presentation_amount'];
        $mailingObject->currency = $args ['presentation_currency'];
        $mailingObject->usage = $args ['identification_shortid'];

        $template = 'kPlugin_' . $this->oPlugin->kPlugin . '_pp-reminder';
        $mail = sendeMail( $template , $mailingObject);
        Jtllog::writeLog('templateId: ' . print_r($template, 1));
        Jtllog::writeLog('mail: ' . print_r($mail, 1));
    }
}
