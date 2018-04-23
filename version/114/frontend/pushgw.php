<?php
/*
 * Basic abstract class for payment methods.
 *
 * DESC
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/' . $oPlugin->nVersion . '/paymentmethod/helper/PushNotificationHandler.php';

$xml = file_get_contents("php://input");
//mail('david.owusu@heidelpay.com', 'XML raw', print_r($xml,1));
$pushHandler = new PushNotificationHandler($xml);
debugLog('push received '.$pushHandler->isHashValid);

sleep(1);
/*if($pushHandler->referenceExists()) {
}*/

if($pushHandler->isTimeStampNew()) {
    $pushHandler->saveResponse();
    $pushHandler->handlePush();
}

function debugLog($input)
{
    Jtllog::writeLog(print_r($input,1),4);
}