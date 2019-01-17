<?php
/*
 * Gateway for incoming heidelpay push notifications
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once $oPlugin->cPluginPfad . 'paymentmethod/helper/PushNotificationHandler.php';

$xml = file_get_contents("php://input");
$pushHandler = new PushNotificationHandler($xml);

sleep(3);
//TODO: adjust delay for release

if($pushHandler->isTimeStampNew() && $pushHandler->saveResponse()) {
    $pushHandler->handlePush();
}