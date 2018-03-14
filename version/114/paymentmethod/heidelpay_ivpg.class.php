<?php
/**
 * Created by PhpStorm.
 * User: David.Owusu
 * Date: 08.03.2018
 * Time: 13:55
 */

require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/' .$oPlugin->nVersion. '/paymentmethod/heidelpay_standard.class.php';


class heidelpay_ivpg extends heidelpay_standard
{
    private $paymentCode = 'HPIVPG';

    public function prepareRequest($order, $currentPaymentMethod, $notifyURL)
    {
        parent::prepareRequest($order, $currentPaymentMethod, $notifyURL);
        $this->addBasketId($currentPaymentMethod, $order);
    }
}