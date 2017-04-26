<?php
/*
 * SUMMARY
 *
 * DESC
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright � 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author Ronja Wann
 * @category JTL
 *
 *
 */



if (preg_match('/[0-9]{3}\.[0-9]{3}\.[0-9]{3}/', $_GET ['hperror'])) {
    include_once(PFAD_ROOT . PFAD_INCLUDES . 'plugins/heidelpay_standard/version/112/paymentmethod/heidelpay_standard.class.php');

    $heidelpayError = $_GET['hperror'];

    $heidelpay_sbase = new heidelpay_standard();



    $local = ($_SESSION ['cISOSprache'] == 'ger') ? 'de_DE' : 'en_US';


    $customerErrorMessage = new \Heidelpay\CustomerMessages\CustomerMessage($local);

    $divStart = '<div class="alert alert-danger"><strong>Error:</strong><br>';
    $divEnd = '</div>';


    $hpErrorMsg = htmlspecialchars_decode($customerErrorMessage->getMessage($heidelpayError));


    $errorSnip = $divStart . ' ' . $hpErrorMsg . ' ' . $divEnd;
    pq("#content")->prepend($errorSnip);

    unset($_SESSION['heidelpayErrorCode']);
}
