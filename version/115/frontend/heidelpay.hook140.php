<?php
/*
 * SUMMARY
 *
 * DESC
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright � 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author Ronja Wann
 * @category JTL
 *
 *
 */
require_once PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cVerzeichnis. '/vendor/autoload.php';

use \Heidelpay\MessageCodeMapper\MessageCodeMapper;

if (isset($_GET ['hperror'])) {
    if (preg_match('/[0-9]{3}\.[0-9]{3}\.[0-9]{3}/', $_GET ['hperror'])) {
        $heidelpayError = $_GET['hperror'];

        $local = ($_SESSION ['cISOSprache'] == 'ger') ? 'de_DE' : 'en_US';

        $customerErrorMessage = new MessageCodeMapper($local);

        $divStart = '<div class="alert alert-danger"><strong>Error:</strong><br>';
        $divEnd = '</div>';

        $hpErrorMsg = htmlspecialchars_decode($customerErrorMessage->getMessage($heidelpayError));

        $errorSnip = $divStart . ' ' . $hpErrorMsg . ' ' . $divEnd;
        pq("#content")->prepend($errorSnip);
    }
}

if (isset($_GET ['hperrorcom'])) {
    $divStart = '<div class="alert alert-danger"><strong>Error:</strong><br>';
    $divEnd = '</div>';

    $hpErrorMsg = $oPlugin->oPluginSprachvariableAssoc_arr['hp_b2b_denied'];

    $errorSnip = $divStart . ' ' . $hpErrorMsg . ' ' . $divEnd;
    pq('#content')->prepend($errorSnip);
}

if (isset($_GET ['hperroradd'])) {
    $divStart = '<div class="alert alert-danger"><strong>Error:</strong><br>';
    $divEnd = '</div>';

    $hpErrorMsg = $oPlugin->oPluginSprachvariableAssoc_arr['hp_equal_address'];

    $errorSnip = $divStart . ' ' . $hpErrorMsg . ' ' . $divEnd;
    pq('#content')->prepend($errorSnip);
}
