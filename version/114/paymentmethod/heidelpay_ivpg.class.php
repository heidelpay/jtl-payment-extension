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


class heidelpay_ivpg extends heidelpay_standard
{
    /**
     * @param $order
     * @param $currentPaymentMethod
     * @param $notifyURL
     */
    public function prepareRequest(Bestellung $order, $currentPaymentMethod, $notifyURL)
    {
        parent::prepareRequest($order, $currentPaymentMethod, $notifyURL);
        $this->addBasketId($currentPaymentMethod, $order);
    }
}