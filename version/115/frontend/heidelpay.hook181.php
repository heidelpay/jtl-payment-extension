<?php
/**
 * Hook 181: Perform automatic finalize for secured invoice when JTL WAWI synchronize with the online shop.
 * Reservation gets finalized if not happened yet and the order was send.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author Ronja Wann, David Owusu
 * @category JTL
*/


require_once PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/vendor/autoload.php';
require_once __DIR__ . '/xmlQuery.php';

use Heidelpay\XmlQuery;

#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

$oBestellung = $args_arr['oBestellung'];
$query = "SELECT tbestellung.kBestellung, tzahlungsart.cModulId
            FROM tbestellung
            LEFT JOIN tzahlungsart ON tbestellung.kZahlungsart = tzahlungsart.kZahlungsart
            WHERE tbestellung.kBestellung = :kBestellung";

$orderRef = Shop::DB()->executeQueryPrepared($query, ['kBestellung' => (int)$oBestellung->kBestellung], 1);

$_query_live_url = 'https://heidelpay.hpcgw.net/TransactionCore/xml';
$_query_sandbox_url = 'https://test-heidelpay.hpcgw.net/TransactionCore/xml';

$url = $_query_sandbox_url;
$modulId = $orderRef->cModulId;

$sandboxMode = 1;
if ($oPlugin->oPluginEinstellungAssoc_arr [$modulId . '_transmode'] === 'LIVE') {
    $url = $_query_live_url;
    $sandboxMode = 0;
}

$payMethod = explode('_', $modulId);

// if Versand oder Teilversand - Status s. defines_inc.php
if (($args_arr['status'] === 4 OR $args_arr['status'] === 5)AND
    $payMethod['2'] === 'heidelpaygesicherterechnungplugin') {
    preg_match('/[0-9]{4}\.[0-9]{4}\.[0-9]{4}/', $oBestellung->cKommentar, $result);

    if (!empty($result[0])) {
        $xml_params = array(
            'type' => 'STANDARD',
            'methods' => array('IV'),
            'types' => array('PA'),
            'identification' => $result,
            'procRes' => 'ACK',
            'transType' => 'PAYMENT'
        );


        $xmlQueryClass = new XmlQuery();

        $config = array(
            'sandbox' => $sandboxMode,
            'security_sender' => $oPlugin->oPluginEinstellungAssoc_arr ['sender'],
            'user_login' => $oPlugin->oPluginEinstellungAssoc_arr ['user'],
            'user_password' => $oPlugin->oPluginEinstellungAssoc_arr ['pass']
        );

        $finalizedOrder = Shop::DB()->select('xplugin_heidelpay_standard_finalize', 'cshort_id', $result[0]);

        //if finalize wasn't found in the database, do finalize
        if ($finalizedOrder === null) {
            $res = $xmlQueryClass->doRequest(
                array(
                    'load' => urlencode($xmlQueryClass->getXMLRequest($config, $xml_params))
                ),
                $url
            );

            $resXMLObject = new SimpleXMLElement($res);
            $resUniquieId = (string)$resXMLObject->Result->Transaction->Identification->UniqueID;
            $paymentObject = new Heidelpay\PhpPaymentApi\PaymentMethods\InvoiceB2CSecuredPaymentMethod();

            $paymentObject->getRequest()->authentification(
                $oPlugin->oPluginEinstellungAssoc_arr ['sender'],
                $oPlugin->oPluginEinstellungAssoc_arr ['user'],
                $oPlugin->oPluginEinstellungAssoc_arr ['pass'],
                (string)$resXMLObject->Result->Transaction['channel'],
                $sandboxMode
            );

            $paymentObject->getRequest()->basketData(
                $oBestellung->cBestellNr,
                $oBestellung->fGesamtsumme,
                (string)$resXMLObject->Result->Transaction->Payment->Presentation->Currency,
                $oBestellung->cSession
            );
            $paymentObject->finalize($resUniquieId);

            if ($paymentObject->getResponse()->isError()
                && $paymentObject->getResponse()->getError()['code'] !== '700.400.800'
            ) {
                $errorMail = $oPlugin->oPluginEinstellungAssoc_arr ['reportErrorMail'];
                $errorCode = $paymentObject->getResponse()->getError();
                $subject = 'heidelpay: Order ID ' . $oBestellung->kBestellung . ' report shipment failed';
                $errorText = 'Report shipment for order' . $oBestellung->kBestellung . ' in Shop ' .
                    Shop::getURL() . ' failed.
                    Error messsage: ' . print_r($errorCode['message'], true);
                if (!empty($errorMail) && filter_var($errorMail, FILTER_VALIDATE_EMAIL)) {
                    $address = [
                        [
                            'cName' => $errorMail,
                            'cMail'=> $errorMail
                        ]
                    ];
                    $mailer = new SimpleMail();
                    $mailer->setBetreff($subject);
                    $mailer->setBodyHTML($errorText);
                    $sendResult = $mailer->send($address);
                } else {
                    Jtllog::writeLog($subject . ': ' . $errorText, JTLLOG_LEVEL_ERROR);
                }

            } else {
                Shop::DB()->insert('xplugin_heidelpay_standard_finalize', (object)[
                    'cshort_id' => $result[0],
                    'kBestellung' => $oBestellung->kBestellung
                ]);
            }
        }
    }
}
