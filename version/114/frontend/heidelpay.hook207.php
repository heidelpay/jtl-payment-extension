<?php
/*
 * Hook 207: save a relation between temporary order number and the actual order.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright � 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
*/
$order = $args_arr['oBestellung'];

if(!empty($_SESSION['hp_temp_orderId']) AND $_SESSION['hp_temp_orderId'] != $order->cBestellNr) {

    $orderReference = new stdClass();
    $orderReference->cBestellNr = $order->cBestellNr;
    $orderReference->cTempBestellNr = $_SESSION['hp_temp_orderId'];

    Shop::DB()->insert('xplugin_heidelpay_standard_order_reference', $orderReference);

    unset($_SESSION['hp_temp_orderId']);
}