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
require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/' .$oPlugin->nVersion. '/paymentmethod/heidelpay_standard.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods;

class heidelpay_cc extends heidelpay_standard
{

    public function setPaymentObject()
    {
        $this->paymentObject = new PaymentMethods\CreditCardPaymentMethod();
    }

    public function sendPaymentRequest()
    {
        if ($this->getBookingMode($this->oPlugin, $this->currentPaymentMethod) === 'DB') {
            $this->paymentObject->debit($this->getPaymentFrameOrigin(), 'FALSE');
        } else {
            $this->paymentObject->authorize($this->getPaymentFrameOrigin(), 'FALSE');
        }
    }
}
