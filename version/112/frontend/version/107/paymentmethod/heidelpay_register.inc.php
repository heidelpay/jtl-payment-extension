<?php

/* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright � 2016-present Heidelberger Payment GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo
* @category JTL
*/

$debug = false;
if (!isset($_REQUEST['hp_go'])) {
    $_SESSION['hpLastPost'] = $_POST;
    global $smarty;
    $Einstellungen = getEinstellungen(array(CONF_ZAHLUNGSARTEN));
  #echo '<pre>'.print_r($Einstellungen['zahlungsarten'], 1).'</pre>';

  switch ($Zahlungsart->cModulId) {
  case 'za_hpcc_jtl':
    $payCode = 'cc';
    break;
  case 'za_hpdc_jtl':
    $payCode = 'dc';
    break;
  case 'za_hpdd_jtl':
    $payCode = 'dd';
    break;
  }

    $capture = false;
    $prefixUP  = strtoupper('HP'.$payCode);
    $prefixLOW = strtolower($prefixUP);
    define($prefixUP.'_SECURITY_SENDER', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_sender']);
    define($prefixUP.'_USER_LOGIN', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_user_login']);
    define($prefixUP.'_USER_PWD', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_user_pwd']);
    define($prefixUP.'_TRANSACTION_CHANNEL', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_channel']);
    define($prefixUP.'_TRANSACTION_MODE', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_transactionmode']);
    define($prefixUP.'_PAYMENT_MODE', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_paymentmode']);
    define($prefixUP.'_MODULE_MODE', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_modulemode']);

    if ($Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_modulemode'] == 'DIRECT') {
        if (isset($_SESSION['hpModuleMode'])) {
            unset($_SESSION['hpModuleMode']);
        }
        include_once(PFAD_ROOT.PFAD_INCLUDES_MODULES.'heidelpay/class.heidelpay.php');
        $hp = new heidelpay();
        $hp->actualPaymethod = strtoupper($payCode);
        $user_id  = $_SESSION['Kunde']->kKunde;
        $orderId  = 'User '.$user_id.'-'.date('YmdHis');
        $amount   = $_SESSION['Warenkorb']->PositionenArr[0]->cGesamtpreisLocalized[0][$_SESSION['Waehrung']->cISO] + $_SESSION['Versandart']->fEndpreis;
        $currency = $_SESSION['Waehrung']->cISO;
        $language = $_SESSION['cISOSprache']=='ger'?'DE':'EN';
        $user = $_SESSION['Kunde'];
        $userData = array(
      'firstname' => $user->cVorname,
      'lastname'  => $user->cNachname,
      'company' => $user->cFirma,
      'street'    => $user->cStrasse,
      'zip'       => $user->cPLZ,
      'city'      => $user->cOrt,
      'country'   => $user->cLand,
      'email'     => $user->cMail,
    );
        $payMethod = 'RG';
        $data = $hp->prepareData($orderId, $amount, $currency, $payCode, $userData, $language, $payMethod);
        if ($debug) {
            echo '<pre>'.print_r($data, 1).'</pre>';
        }
        $res = $hp->doRequest($data);
        if ($debug) {
            echo '<pre>resp('.print_r($hp->response, 1).')</pre>';
        }
        if ($debug) {
            echo '<pre>'.print_r($res, 1).'</pre>';
        }
        $res = $hp->parseResult($res);
        if ($debug) {
            echo '<pre>'.print_r($res, 1).'</pre>';
        }
        $processingresult = $res['result'];
        $redirectURL      = $res['url'];
        $base = 'http://'.$_SERVER['HTTP_HOST'].'/';
        $src = $base."heidelpay_fail.php?hperror=1";
        if ($processingresult == "ACK" && strstr($redirectURL, "http")) {
            $src = $redirectURL;
        }
        parse_str($redirectURL, $output);
        $hpSID = current($output);

        $hpActionURL = str_replace('payment.prc', '', $hp->url).'submitPayment.prc;jsessionid='.$hpSID
      .'?FRONTEND.RESPONSE_URL='.$data['FRONTEND.RESPONSE_URL']
      .'&IDENTIFICATION.TRANSACTIONID='.$data['IDENTIFICATION.TRANSACTIONID']
      .'&FRONTEND.POPUP='.$data['FRONTEND.POPUP']
      .'&FRONTEND.NEXT_TARGET='.$data['FRONTEND.NEXT_TARGET']
      .'&frontendCancelled=false';
        $smarty->assign('HP_PAYMETHOD', strtoupper($payCode));
        $smarty->assign('HP_ACTIONURL', $hpActionURL);
        $smarty->assign('HP_LANGUAGE', $language);
        $smarty->assign('HP_PAYMENTTYPE', $payMethod);
        $smarty->assign('HP_IFRAME', $src);
    } else {
        // Nachgelagert
    $zusatzangabenDa=true;
    }
} else {
    // Alles registriert
  $zusatzangabenDa=true;
}
