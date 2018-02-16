<?php
/* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo
* @category JTL
*/

require_once("includes/globalinclude.php");
//session starten
$session = new Session();
$sid = session_name().'='.session_id();

$returnvalue=$_POST['PROCESSING_RESULT'];
if ($returnvalue) {
    include_once(PFAD_ROOT.PFAD_INCLUDES_MODULES.'heidelpay/class.heidelpay.php');
    $hp = new heidelpay();
    $base = $hp->pageURL;
    $params = '';
    $orderID = (int)preg_replace('/_\d*/', '', $_POST['IDENTIFICATION_TRANSACTIONID']);
    if ($_POST['PAYMENT_CODE'] == 'PP.PA') {
        $params = '&pcode='.$_POST['PAYMENT_CODE'].'&';
        foreach ($hp->importantPPFields as $k => $v) {
            $params.= $v.'='.$_POST[$v].'&';
        }
        $status = 'PENDING';
    } else {
        $params.= '&code='.$_POST['PAYMENT_CODE'];
        $status = 'SUCCESS';
    }
    if (strstr($returnvalue, "ACK")) {
        if (strpos($_POST['PAYMENT_CODE'], 'RG') === false) {
            $hp->setStatus($status, $orderID, $_POST['PRESENTATION_AMOUNT']);
        } else {
            $hp->setStatus('PENDING', $orderID);
        }
        if ($_POST['PROCESSING_STATUS_CODE'] == '90' && $_POST['AUTHENTICATION_TYPE'] == '3DSecure') {
            print $base."heidelpay_3dsecure_return.php?order_id=".$orderID.'&'.$sid;
        } else {
            print $base."heidelpay_redirect.php?order_id=".$orderID.'&uniqueId='.$_POST['IDENTIFICATION_UNIQUEID'].$params.'&'.$sid;
        }
    } elseif ($_POST['FRONTEND_REQUEST_CANCELLED'] == 'true') {
        $hp->setStatus('CANCEL', $orderID);
        print $base."heidelpay_redirect.php?cancel=1".'&'.$sid;
    } else {
        $hp->setStatus('FAILED', $orderID);
        print $base."heidelpay_redirect.php?hperror=".$_POST['PROCESSING_RETURN'].'&'.$sid;
    }
} else {
    echo 'FAIL';
}
