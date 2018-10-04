<?php
/*
 * Invoice paymentmethod
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/' .$oPlugin->nVersion. '/paymentmethod/heidelpay_standard.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods\InvoicePaymentMethod;

class heidelpay_iv extends heidelpay_standard
{

    public function setPaymentObject()
    {
        $this->paymentObject = new InvoicePaymentMethod();
    }

    public function setPayInfo($args, $order)
    {
        //Prepare customer object for mailObject
        $tkunde = new stdClass();
        $tkunde->cMail = $order->oRechnungsadresse->cMail;
        $tkunde->kSprache = $order->kSprache;

        $mailingObject = new stdClass();
        $mailingObject->tkunde = $tkunde;
        $mailingObject->accIban = $args ['CONNECTOR_ACCOUNT_IBAN'];
        $mailingObject->accBic = $args ['CONNECTOR_ACCOUNT_BIC'];
        $mailingObject->accHolder = $args ['CONNECTOR_ACCOUNT_HOLDER'];
        $mailingObject->amount = $args ['PRESENTATION_AMOUNT'];
        $mailingObject->currency = $args ['PRESENTATION_CURRENCY'];
        $mailingObject->usage = $args ['IDENTIFICATION_SHORTID'];

        $template = 'kPlugin_' . $this->oPlugin->kPlugin . '_iv-reminder';
        $mail= sendeMail( $template , $mailingObject);

        $bookingtext = $mail->bodyText;

        $updateOrder = new stdClass();
        $updateOrder->cKommentar = htmlspecialchars(utf8_decode($bookingtext));

        Shop::DB()->update('tbestellung', 'cBestellNr', htmlspecialchars($order->cBestellNr), $updateOrder);
        Jtllog::writeLog('updated payinfo: '.print_r(shop::DB()->select('tbestellung', 'cBestellNr', htmlspecialchars($order)),1), 4);
    }
}
