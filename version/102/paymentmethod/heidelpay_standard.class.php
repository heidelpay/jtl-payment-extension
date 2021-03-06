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

include_once('../includes/plugins/heidelpay_standard/version/102/paymentmethod/class.heidelpay_sbase.php');

// Constants
define('HP_TRANSACTION_MODE', 'LIVE'); // INTEGRATOR_TEST | CONNECTOR_TEST | LIVE

/**
 * Heidelpay
 */
class heidelpay_standard extends heidelpay_sbase
{
    public function init()
    {
        parent::init();

        $this->name = 'HeidelPay';
        $this->caption = 'Heidelpay';

        $sql = "SELECT * FROM tzahlungsart WHERE cModulId = '{$this->cModulId}'";
        $this->info = $GLOBALS["DB"]->executeQuery($sql, 1);
    }

    public function preparePaymentProcess($order)
    {
        global $Einstellungen, $DB, $smarty, $bestellung;

        $myZV = $_SESSION['Zahlungsart']->cModulId;
        if (empty($myZV)) {
            $myZV = $bestellung->Zahlungsart->cModulId;
        }
      //http://jtl3/includes/plugins/heidelpay_standard/version/100/paymentmethod/template/heidelpay.png
      $kPlugin = gibkPluginAuscModulId($myZV);
        if ($kPlugin > 0) {
            $oPlugin = new Plugin($kPlugin);
        } else {
            return false;
        }
        $actZVPrefix = 'kPlugin_'.$oPlugin->kPlugin.'_';
        $actZV = str_replace($actZVPrefix, '', $myZV);
      #echo 'actZV: '.$actZV.'<br>';
      #echo $myZV.'<br>';
      #echo $kPlugin.'<br>';
      #echo '<pre>'.print_r($_SESSION['Zahlungsart'], 1).'</pre>';
      #echo '<pre>'.print_r($oPlugin, 1).'</pre>'; exit();
      $hash = $this->generateHash($order);
        if ($order->cId != '') {
            $hash = $order->cId;
        }
      #echo 'pPP<br>';
      $this->moduleID = $myZV;
        $this->init(0);
      #echo '<pre>'.print_r($this, 1).'</pre>';

      $notifyURL = $this->getNotificationURL($hash);
      #echo $notifyURL;

      $debug = false;

        $payCode = strtolower($oPlugin->oPluginEinstellungAssoc_arr[$myZV.'_paycode']);
        $prefixUP  = strtoupper('HP'.$payCode);
        $prefixLOW = strtolower($prefixUP);
        $capture = false;

        define($prefixUP.'_SECURITY_SENDER', $oPlugin->oPluginEinstellungAssoc_arr['sender']);
        define($prefixUP.'_USER_LOGIN', $oPlugin->oPluginEinstellungAssoc_arr['user']);
        define($prefixUP.'_USER_PWD', $oPlugin->oPluginEinstellungAssoc_arr['pass']);
        define($prefixUP.'_TRANSACTION_CHANNEL', $oPlugin->oPluginEinstellungAssoc_arr[$myZV.'_channel']);
        define($prefixUP.'_TRANSACTION_MODE', $oPlugin->oPluginEinstellungAssoc_arr[$myZV.'_transmode']);
        define($prefixUP.'_PAYMENT_MODE', $oPlugin->oPluginEinstellungAssoc_arr[$myZV.'_bookingmode']);
      #define($prefixUP.'_MODULE_MODE', $oPlugin->oPluginEinstellungAssoc_arr[$myZV.'_modulemode']);
      define($prefixUP.'_PLUGIN_URL', $oPlugin->cFrontendPfadURL);

        $this->actualPaymethod = strtoupper($payCode);
        $userId  = $_SESSION['Kunde']->kKunde;

        $orderId  = $order->cBestellNr;
        if (empty($orderId)) {
            $orderId = baueBestellnummer();
        }
        $amount   = $order->fGesamtsummeKundenwaehrung; // In Kunden W�hrung
      if (empty($amount)) {
          $amount = $_SESSION["Warenkorb"]->gibGesamtsummeWaren(1);
      }

      #$orderId = $userId;
      $currency = $_SESSION['Waehrung']->cISO;
        $language = $_SESSION['cISOSprache']=='ger'?'DE':'EN';

        $user = $_SESSION['Kunde'];
        $userData = array(
        'firstname' => $user->cVorname,
        'lastname'  => $user->cNachname,
        'street'    => $user->cStrasse.' '.$user->cHausnummer,
        'zip'       => $user->cPLZ,
        'city'      => $user->cOrt,
        'country'   => $user->cLand,
        'email'     => $user->cMail,
      );
        $payMethod = constant($prefixUP.'_PAYMENT_MODE');
      //$payMethod = 'DB';
      $changePayType = array('gp', 'su', 'eps', 'idl');
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

      if ($this->actualPaymethod == 'BS' && !$this->equalAddress($bestellung)) {
          $_SESSION['heidelpayLastError'] = 'Liefer- und Rechnungsadresse m�ssen identisch sein.';
          $src = 'bestellvorgang.php?hperror='.$_SESSION['heidelpayLastError'];
          header('Location: '.$src);
          exit();
      }

        $data = $this->prepareData($orderId, $amount, $currency, $payCode, $userData, $language, $payMethod);
        if (class_exists('FB')) {
            FB::log($data);
        }
      // Response auf Notify.php umlenken.
      if (strpos($_SERVER['REMOTE_ADDR'], '127.0.0') === false) {
          $data['FRONTEND.RESPONSE_URL'] = $notifyURL;
      }
        $data['CRITERION.RESPONSE_URL'] = $notifyURL;
      #$data['FRONTEND.RESPONSE_URL'].= '&ph='.$hash;
      if ($debug) {
          echo '<pre>'.print_r($data, 1).'</pre>';
      }
        $res = $this->doRequest($data);
        if ($debug) {
            echo '<pre>resp('.print_r($this->response, 1).')</pre>';
        }
        if ($debug) {
            echo '<pre>'.print_r($res, 1).'</pre>';
        }
        $res = $this->parseResult($res);
        if (class_exists('FB')) {
            FB::log($res);
        }
        if ($debug) {
            echo '<pre>'.print_r($res, 1).'</pre>';
        }
        if ($debug) {
            exit();
        }

        if ($res['all']['PROCESSING.STATUS.CODE'] == '80' && $res['all']['PROCESSING.RETURN.CODE'] == '000.200.000' && $res['all']['PROCESSING.REASON.CODE'] == '00') {
            $src = $res['all']['PROCESSING.REDIRECT.URL'];
            if ($this->actualPaymethod == 'BS') {
                header('Location: '.$src);
                exit();
            }
        }

        $processingresult = $res['result'];
        $redirectURL      = $res['url'];
        $base = 'http://'.$_SERVER['HTTP_HOST'].'/';
        $src = $base;
        if ($processingresult == "ACK" && strstr($redirectURL, "http")) {
            $src = $redirectURL;
        } else {
            $_SESSION['heidelpayLastError'] = $res['all']['PROCESSING.RETURN'];
            $src = 'bestellvorgang.php?hperror='.$_SESSION['heidelpayLastError'];
            header('Location: '.$src);
            exit();
        }
        $hpIframe = '<center><iframe src="'.$src.'" frameborder="0" width="400" height="650"></iframe></center>';
        $smarty->assign('heidelpay_iframe', $hpIframe);
    }

    public function handleNotification($order, $paymentHash, $args)
    {
        if (strstr($args['PROCESSING_RESULT'], 'ACK')) {
            unset($_SESSION['heidelpayLastError']); // damit ggf. vorherige Fehler gel�scht werden
        if ($this->verifyNotification($order, $paymentHash, $args)) {
            $incomingPayment->fBetrag = $order->fGesamtsummeKundenwaehrung;
            $incomingPayment->cISO = $order->Waehrung->cISO;
            $this->addIncomingPayment($order, $incomingPayment);
          //mail('eilers@heidelpay.de', 'DEBUG1', print_r($args,1));
          //if ($args['PAYMENT_CODE'] != 'IV.PA'){ // Nur wenn nicht BillSAFE
          if (strpos($args['ACCOUNT_BRAND'], 'BILLSAFE') === false) { // Nur wenn nicht BillSAFE
            //mail('eilers@heidelpay.de', 'DEBUG2', print_r($order,1));
            $this->setOrderStatusToPaid($order);
              $this->sendConfirmationMail($order);
          }
            $this->updateNotificationID($order->kBestellung, $args['IDENTIFICATION_UNIQUEID']);
        }
        } elseif ($args['FRONTEND_REQUEST_CANCELLED'] == 'true') {
            $_SESSION['heidelpayLastError'] = 'Cancelled by User';
        } else {
            $_SESSION['heidelpayLastError'] = $args['PROCESSING_RETURN'];
        }

      // Heidelpay redirects to:
      echo $this->getReturnURL($order).'&hperror='.$_SESSION['heidelpayLastError'];
    }

    /**
     * @return boolean
     * @param Bestellung $order
     * @param array $args
     */
    public function verifyNotification($order, $paymentHash, $args)
    {
        extract($args);
      /*
      if ($IDENTIFICATION_TRANSACTIONID != $paymentHash)
      {
        return false;
      }		
      return true;
       */
      if ($CLEARING_AMOUNT != number_format($order->fGesamtsummeKundenwaehrung, 2, '.', '')) {
          #echo $CLEARING_AMOUNT.' != '.$order->fGesamtsummeKundenwaehrung.'<br>';
        return false;
      }

        if ($CLEARING_CURRENCY != $order->Waehrung->cISO) {
            return false;
        }
        return true;
    }
    
    public function finalizeOrder($order, $hash, $args)
    {
        global $cEditZahlungHinweis;
        extract($args);
        
        
        
        if ($PROCESSING_RESULT == "ACK") {
            return true;
        } else {
            $cEditZahlungHinweis = $_POST['PROCESSING_RETURN'];
            return false;
        }
    }
    

    public function equalAddress($order)/*{{{*/
    {
        if ($order->Lieferadresse == 0) {
            return true;
        } // Liefer und Rechnungsadresse gleich
      $diffs = 0;
        foreach ($order->oRechnungsadresse as $k => $v) {
            if ($order->Lieferadresse->$k != $v) {
                $diffs++;
            }
        }
        return $diffs == 0;
    }/*}}}*/
}
