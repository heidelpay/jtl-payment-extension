<?php
/*
 * Debit card paymentmethod
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/' .$oPlugin->nVersion. '/paymentmethod/heidelpay_standard.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods\DebitCardPaymentMethod;

class heidelpay_dc extends heidelpay_standard
{

    public function setPaymentObject()
    {
        $this->paymentObject = new DebitCardPaymentMethod();
    }

    public function sendPaymentRequest()
    {
        global $oPlugin;
        $cssPath = Shop::getURL() . '/' . PFAD_PLUGIN . '/' . $oPlugin->cVerzeichnis .'/version/'
            . $oPlugin->nVersion . '/paymentmethod/template/css/hppaymentframe.css';

        if ($this->getBookingMode($this->oPlugin, $this->moduleID) === 'DB') {
            $this->paymentObject->debit($this->getPaymentFrameOrigin(), 'FALSE', $cssPath);
        } else {
            $this->paymentObject->authorize($this->getPaymentFrameOrigin(), 'FALSE', $cssPath);
        }
    }
}
