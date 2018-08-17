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
require_once $oPlugin->cPluginPfad . 'paymentmethod/heidelpay_standard.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods\InvoicePaymentMethod;

class heidelpay_iv extends heidelpay_standard
{

    public function setPaymentObject()
    {
        $this->paymentObject = new InvoicePaymentMethod();
    }

    public function setPayInfo($post, $orderId)
    {
        $repl = [
            '{PRESENTATION_AMOUNT}' => $post['PRESENTATION_AMOUNT'],
            '{PRESENTATION_CURRENCY}' => $post['PRESENTATION_CURRENCY'],
            '{ACCOUNT_HOLDER}' => $post['CONNECTOR_ACCOUNT_HOLDER'],
            '{ACCOUNT_IBAN}' => $post['CONNECTOR_ACCOUNT_IBAN'],
            '{ACCOUNT_BIC}' => $post['CONNECTOR_ACCOUNT_BIC'],
            '{SHORTID}' => $post['IDENTIFICATION_SHORTID'],
        ];

        $bookingtext = strtr(IV_PAY_INFO, $repl);

        $updateOrder = new stdClass();
        $updateOrder->cKommentar = htmlspecialchars(utf8_decode($bookingtext));

        Shop::DB()->update('tbestellung', 'cBestellNr', htmlspecialchars($orderId), $updateOrder);
        Jtllog::writeLog('updated payinfo: '.print_r(shop::DB()->select('tbestellung', 'cBestellNr', htmlspecialchars($orderId)),1), 4);
    }
}
