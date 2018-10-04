<?php
/*
 * Credit card paymentmethod
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once $oPlugin->cPluginPfad . 'paymentmethod/heidelpay_standard.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods\CreditCardPaymentMethod;

class heidelpay_cc extends heidelpay_standard
{
    public function setPaymentObject()
    {
        $this->paymentObject = new CreditCardPaymentMethod();
    }

    public function sendPaymentRequest()
    {
        global $oPlugin;
        $cssPath = $oPlugin->cFrontendPfadURL . 'css/hppaymentframe.css';

        if ($this->getBookingMode($this->oPlugin, $this->moduleID) === 'DB') {
            $this->paymentObject->debit($this->getPaymentFrameOrigin(), 'FALSE', $cssPath);
        } else {
            $this->paymentObject->authorize($this->getPaymentFrameOrigin(), 'FALSE', $cssPath);
        }
    }
}
