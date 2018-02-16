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

$debug = true;
if (!isset($_REQUEST['3ds'])) {
    ob_start();
    if (empty($_SESSION['Zahlungsart']->cModulId)) {
        $payCode = str_replace(array('za_hp', '_jtl'), '', $bestellung->Zahlungsart->cModulId);
    } else {
        $payCode = str_replace(array('za_hp', '_jtl'), '', $_SESSION['Zahlungsart']->cModulId);
    }
    $prefixUP  = strtoupper('HP'.$payCode);
    $prefixLOW = strtolower($prefixUP);
    $capture = false;
    if ($Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_modulemode'] == 'DIRECT') {
        $capture = true;
    }
  
    define($prefixUP.'_SECURITY_SENDER', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_sender']);
    define($prefixUP.'_USER_LOGIN', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_user_login']);
    define($prefixUP.'_USER_PWD', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_user_pwd']);
    define($prefixUP.'_TRANSACTION_CHANNEL', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_channel']);
    define($prefixUP.'_TRANSACTION_MODE', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_transactionmode']);
    define($prefixUP.'_PAYMENT_MODE', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_paymentmode']);
    define($prefixUP.'_MODULE_MODE', $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_modulemode']);
  #[zahlungsart_hpcc_paymentmode] => DB
  #[zahlungsart_hpcc_modulemode] => DIRECT

  #echo '<pre>'.print_r($Einstellungen['zahlungsarten'], 1).'</pre>';
  if (empty($_SESSION['Kunde']) && $_POST['kKunde'] > 0) {
      $_SESSION['Kunde'] = new Kunde($_POST['kKunde']);
  }

    include_once(PFAD_ROOT.PFAD_INCLUDES_MODULES.'heidelpay/class.heidelpay.php');
    $hp = new heidelpay();
    $hp->actualPaymethod = strtoupper($payCode);
    $user_id  = $_SESSION['Kunde']->kKunde;
    $orderId  = $bestellung->cBestellNr;
    if (empty($orderId)) {
        $orderId = baueBestellnummer();
    }
    $amount   = $bestellung->fGesamtsumme;
    if (empty($amount)) {
        $amount = $_SESSION["Warenkorb"]->gibGesamtsummeWaren(1);
    }
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
    $payMethod = $Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_paymentmode'];
    $changePayType = array('gp', 'su', 'tp');
    if (in_array($payCode, $changePayType)) {
        $payCode = 'OT';
    }
    if (empty($payMethod)) {
        $payMethod = 'DB';
    }
    if ($payCode == 'OT' && $payMethod == 'DB') {
        $payMethod = 'PA';
    }
    if (strtoupper($payCode) == 'PP' && $payMethod == 'DB') {
        $payMethod = 'PA';
    } // Vorkasse immer PA
  if ($isBestellAgain) {
      $_SESSION['hpModuleMode'] = 'AFTER';
      $capture = false;
      $payMethod = 'DB';
      $orderId.= '-'.date('YmdHis');
  }
    $data = $hp->prepareData($orderId, $amount, $currency, $payCode, $userData, $language, $payMethod, $capture, $_SESSION['hpUniqueID']);
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
  // 3D Secure
  if ($res['all']['PROCESSING.STATUS.CODE'] == '80' && $res['all']['PROCESSING.RETURN.CODE'] == '000.200.000' && $res['all']['PROCESSING.REASON.CODE'] == '00') {
      $src = $res['all']['PROCESSING.REDIRECT.URL'];
      $hpIframe = '<iframe src="about:blank" frameborder="0" width="400" height="600" name="heidelpay_frame"></iframe>';
      $hpIframe.= '<form method="post" action="'.$src.'" target="heidelpay_frame" id="heidelpay_frame">';
      $hpIframe.= '<input type="hidden" name="TermUrl" value="'.$res['all']['PROCESSING.REDIRECT.PARAMETER.TermUrl'].'">';
      $hpIframe.= '<input type="hidden" name="PaReq" value="'.$res['all']['PROCESSING.REDIRECT.PARAMETER.PaReq'].'">';
      $hpIframe.= '<input type="hidden" name="MD" value="'.$res['all']['PROCESSING.REDIRECT.PARAMETER.MD'].'">';
      $hpIframe.= '</form>';
      $hpIframe.= '<script>document.getElementById("heidelpay_frame").submit();</script>';
      $_SESSION['HEIDELPAY_IFRAME'] = $hpIframe;
      $_SESSION['hpLastPost'] = $_POST;
      $smarty->assign('heidelpay_iframe', $hpIframe);
    #header('Location: heidelpay_3dsecure.php?'.session_name().'='.session_id());
    #exit();
    return;
  }
    $_SESSION['hpLastPost'] = $_POST;

  // Um sicher zu stellen das am Ende auf die Heidelpay Success gesprungen wird
  if ($Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_modulemode'] == 'AFTER' || empty($Einstellungen['zahlungsarten']['zahlungsart_'.$prefixLOW.'_modulemode'])) {
      $_SESSION['hpModuleMode'] = 'AFTER';
  }
    $processingresult = $res['result'];
    $redirectURL      = $res['url'];
    $base = 'http://'.$_SERVER['HTTP_HOST'].'/';
    $src = $base."heidelpay_fail.php?hperror=1&".session_name().'='.session_id();
    if ($processingresult == "ACK" && strstr($redirectURL, "http")) {
        $src = $redirectURL;
    }
    $smarty->assign('isHeidelPay', true);
    if ((($payCode == 'cc' && HPCC_MODULE_MODE == 'DIRECT')
    || ($payCode == 'dc' && HPDC_MODULE_MODE == 'DIRECT')
    || ($payCode == 'dd' && HPDD_MODULE_MODE == 'DIRECT'))
    && ($payMethod == 'DB' || $payMethod == 'PA')) {
        // Bei DB für CC / DC / DD keinen IFrame anzeigen
      if ($isBestellAgain) {
          $smarty->assign('heidelpay_iframe', $src);
      } else {
          $hp->setStatus('SUCCESS', $orderId);
      }
    } else {
        if ($hp->actualPaymethod == 'TP' || $hp->actualPaymethod == 'SU') {
            $hpIframe = '<div id="hpBox"><div style="background-color: #666; position:fixed; display:block; margin:0; padding:0; top:0; left:0; opacity: 0.9; -moz-opacity: 0.9; -khtml-opacity: 0.9; filter:alpha(opacity=90); z-index: 1000; width: 100%; height: 100%;"></div>';
            $hpIframe.= '<div style="z-index: 1001; position: absolute; width: 800px; top: 50%; left: 50%; margin-top: -325px; margin-left: -400px;">';
            $hpIframe.= '<iframe src="'.$src.'" frameborder="0" width="800" height="650" style="border: 1px solid #ddd"></iframe><br>';
            $hpIframe.= '<a href="" onClick="document.getElementById(\'hpBox\').style.display=\'none\'; return false;">close</a></div></div>';
        } else {
            $hpIframe = '<iframe src="'.$src.'" frameborder="0" width="400" height="600"></iframe>';
        }
        if ($isBestellAgain) {
            $smarty->assign('heidelpay_iframe', $src);
        } else {
            $smarty->assign('heidelpay_iframe', $hpIframe);
        }
    }
}
