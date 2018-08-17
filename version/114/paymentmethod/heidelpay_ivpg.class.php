<?php
/*
 * Secured Invoice paymentmethod
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once $oPlugin->cPluginPfad . 'paymentmethod/heidelpay_iv.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods\InvoiceB2CSecuredPaymentMethod;

class heidelpay_ivpg extends heidelpay_iv
{
    /**
     * @param $order
     * @param $currentPaymentMethod
     */
    public function prepareRequest(Bestellung $order, $currentPaymentMethod)
    {
        parent::prepareRequest($order, $currentPaymentMethod);
        $this->b2cSecuredCheck($order);
        $this->addBasketId($currentPaymentMethod, $order);
    }

    public function setPaymentObject()
    {
        $this->paymentObject = new InvoiceB2CSecuredPaymentMethod();
    }
}