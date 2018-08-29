<?php
/*
 * Paypal paymentmethod
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/version/' .$oPlugin->nVersion. '/paymentmethod/heidelpay_standard.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods\PayPalPaymentMethod;

class heidelpay_va extends heidelpay_standard
{

    public function setPaymentObject()
    {
        $this->paymentObject = new PayPalPaymentMethod();
    }

    public function prepareRequest(Bestellung $order, $currentPaymentMethod)
    {
        parent::prepareRequest($order, $currentPaymentMethod);

    }

    public function sendPaymentRequest()
    {
        if ($this->getBookingMode($this->oPlugin, $this->moduleID) === 'DB') {
            $this->paymentObject->debit();
        } else {
            parent::sendPaymentRequest();
        }
    }

    public function getCustomerData()
    {
        $user = $_SESSION ['Lieferadresse'];
        $mail = $_SESSION ['Kunde'];

        $userStreet = $user->cStrasse . ' ' . $user->cHausnummer;
        $userData = array(empty($user->cVorname) ? null : $user->cVorname,
            empty($user->cNachname) ? null : $user->cNachname,
            empty($user->cFirma) ? null : $user->cFirma,
            empty($user->kKunde) ? null : $user->kKunde,
            empty($userStreet) ? null : $userStreet,
            empty($user->cBundesland) ? null : $user->cBundesland,
            empty($user->cPLZ) ? null : $user->cPLZ,
            empty($user->cOrt) ? null : $user->cOrt,
            empty($user->cLand) ? null : $user->cLand,
            empty($mail->cMail) ? null : $mail->cMail);

        return $this->encodeData($userData);
    }


}
