<?php
/*
 * Secured direct debit paymentmethod
 *
 * DESC
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/version/' .$oPlugin->nVersion. '/paymentmethod/heidelpay_dd.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods\DirectDebitB2CSecuredPaymentMethod;

/**
 * Class heidelpay_ddpg
 */
class heidelpay_ddpg extends heidelpay_dd
{
    /**
     * @throws \Heidelpay\PhpPaymentApi\Exceptions\UndefinedTransactionModeException
     */
    public function sendPaymentRequest()
    {
        if ($this->getBookingMode($this->oPlugin, $this->moduleID) === 'DB') {
                $this->paymentObject->debit();
        } else {
            $this->paymentObject->authorize();
        }
    }

    public function setPaymentObject()
    {
        $this->paymentObject = new DirectDebitB2CSecuredPaymentMethod();
    }

    public function prepareRequest(Bestellung $order, $currentPaymentMethod)
    {
        parent::prepareRequest($order, $currentPaymentMethod);
        $this->b2cSecuredCheck($order);
        $this->addBasketId($currentPaymentMethod, $order);
    }
}