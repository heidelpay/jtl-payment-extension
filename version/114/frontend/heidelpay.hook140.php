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


if (isset($_GET ['hperror'])) {
    if (preg_match('/[0-9]{3}\.[0-9]{3}\.[0-9]{3}/', $_GET ['hperror'])) {
        include_once PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/version/' .
            $oPlugin->nVersion . '/paymentmethod/heidelpay_standard.class.php';

        $heidelpayError = $_GET['hperror'];

        $local = ($_SESSION ['cISOSprache'] == 'ger') ? 'de_DE' : 'en_US';

        $customerErrorMessage = new \Heidelpay\MessageCodeMapper\MessageCodeMapper($local);

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

    if ($_SESSION ['cISOSprache'] == 'ger') {
        $hpErrorMsg = utf8_decode('Dieses Zahlverfahren steht nicht für Firmenkunden zur Verfügung');
    } else {
        $hpErrorMsg = utf8_decode('This paymentmethod is not available for corporate clients');
    }

    $errorSnip = $divStart . ' ' . $hpErrorMsg . ' ' . $divEnd;
    pq('#content')->prepend($errorSnip);
}

if (isset($_GET ['hperroradd'])) {
    $divStart = '<div class="alert alert-danger"><strong>Error:</strong><br>';
    $divEnd = '</div>';

    if ($_SESSION ['cISOSprache'] == 'ger') {
        $hpErrorMsg = utf8_decode('Rechnungs- und Lieferadresse müssen identisch sein');
    } else {
        $hpErrorMsg = utf8_decode('Billing- and shipping adress have to be equal');
    }

    $errorSnip = $divStart . ' ' . $hpErrorMsg . ' ' . $divEnd;
    pq('#content')->prepend($errorSnip);
}
