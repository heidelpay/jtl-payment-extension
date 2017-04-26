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

include_once('../includes/plugins/heidelpay_standard/version/111/paymentmethod/class.heidelpay_sbase.php');

//cushion error reporting
error_reporting(0);

// Constants
define('HP_TRANSACTION_MODE', 'LIVE'); // INTEGRATOR_TEST | CONNECTOR_TEST | LIVE

/**
 * Heidelpay
 */
class heidelpay_standard extends heidelpay_sbase
{
    public function init($nAgainCheckout = 0)
    {
        parent::init($nAgainCheckout);

        $this->name = 'Heidelpay';
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
        if (strtoupper($payCode) == 'DD') {
            define($prefixUP.'_SEPA_MODE', $oPlugin->oPluginEinstellungAssoc_arr[$myZV.'_sepamode']);
        }
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
        'company' => $user->cFirma,
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
            $hpIframe = '<iframe src="'.$src.'" frameborder="0" width="400" height="650"></iframe>';
        } elseif ($processingresult == "ACK" && strtoupper($payCode) == 'PP') {
            $hpIframe = $this->prepaymentText($res, $language);
            $this->sendConfirmationMail($order);
            $this->setStatus("PENDING", $orderId);
        } else {
            $_SESSION['heidelpayLastError'] = $res['all']['PROCESSING.RETURN'];
            $src = 'bestellvorgang.php?hperror='.$_SESSION['heidelpayLastError'];
            header('Location: '.$src);
            exit();
        }
      
        $smarty->assign('heidelpay_iframe', $hpIframe);
    }

    public function handleNotification($order, $paymentHash, $args)
    {
        $this->init();
        if (strstr($args['PROCESSING_RESULT'], 'ACK')) {
            unset($_SESSION['heidelpayLastError']); // damit ggf. vorherige Fehler geloescht werden
        if ($this->verifyNotification($order, $paymentHash, $args)) {
            
            /* zusatz Email mit Mandatsreferenz-ID für Lastschrift */
            $firma = $GLOBALS["DB"]->executeQuery("select * from tfirma", 1);
            $einstellungen = getEinstellungen(array(CONF_EMAILS));
            $payCode = explode('.', $args['PAYMENT_CODE']);
            if ((strtoupper($payCode[0]) == 'DD') && (!isset($args['TRANSACTION_SOURCE']))) {
                $language = $_SESSION['cISOSprache']=='ger'?'DE':'EN';
                if ($language == 'DE') {
                    include_once('../includes/plugins/heidelpay_standard/version/111/paymentmethod/template/heidelpay_ddMail_de.tpl');
                } else {
                    include_once('../includes/plugins/heidelpay_standard/version/111/paymentmethod/template/heidelpay_ddMail_en.tpl');
                }
                $repl = array(
                    '{ACC_IBAN}'        => $args['ACCOUNT_IBAN'],
                    '{ACC_BIC}'            => $args['ACCOUNT_BIC'],
                    '{ACC_IDENT}'    => $args['ACCOUNT_IDENTIFICATION']
                );
                if ((isset($args['IDENTIFICATION_CREDITOR_ID']) && ($args['IDENTIFICATION_CREDITOR_ID'] != ''))) {
                    $repl['{IDENT_CREDITOR}'] = $args['IDENTIFICATION_CREDITOR_ID'];
                } else {
                    $repl['{IDENT_CREDITOR}'] = '-';
                }
                mail($order->oRechnungsadresse->cMail, constant('DD_MAIL_SUBJECT'), strtr(constant('DD_MAIL_TEXT'), $repl), constant('DD_MAIL_HEADERS'));
            }
            $incomingPayment->fBetrag = number_format($order->fGesamtsummeKundenwaehrung, 2, '.', '');
            $incomingPayment->cISO = $order->Waehrung->cISO;
            $this->addIncomingPayment($order, $incomingPayment);
            
            
            if ($payCode[1] != 'PA') { // Nur wenn nicht Vorkasse, Billsafe od. Rechnung
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
    
    public function prepaymentText($res, $lang = 'EN')
    {
        if ($lang == 'DE') {
            define('PREPAYMENT_TEXT', '<b>Bitte &uuml;berweisen Sie uns den Betrag von {CURRENCY} {AMOUNT} auf folgendes Konto:</b><br /><br />
			Land :         {ACC_COUNTRY}<br>
			Kontoinhaber : {ACC_OWNER}<br>
			Konto-Nr. :    {ACC_NUMBER}<br>
			Bankleitzahl:  {ACC_BANKCODE}<br>
			IBAN:   	   {ACC_IBAN}<br>
			BIC:           {ACC_BIC}<br>
			<br /><br /><b>Geben sie bitte im Verwendungszweck UNBEDINGT die Identifikationsnummer<br />
			{SHORTID}<br />
			und NICHTS ANDERES an.</b>');
        } else {
            define('PREPAYMENT_TEXT', '<b>Please transfer the amount of {CURRENCY} {AMOUNT} to the following account:</b><br /><br />
					Country :         {ACC_COUNTRY}<br>
					Account holder :  {ACC_OWNER}<br>
					Account No. :     {ACC_NUMBER}<br>
					Bank Code:        {ACC_BANKCODE}<br>
					IBAN:   		  {ACC_IBAN}<br>
					BIC:              {ACC_BIC}<br>
					<br><br /><b>Please use the identification number <br />
					{SHORTID}<br />
					as the descriptor and nothing else. Otherwise we cannot match your transaction!</b>');
        }

        $repl = array(
                        '{CURRENCY}' => $res['all']['PRESENTATION_CURRENCY'],
                        '{AMOUNT}' => $res['all']['PRESENTATION_AMOUNT'],
                        '{ACC_COUNTRY}' => $res['all']['CONNECTOR_ACCOUNT_COUNTRY'],
                        '{ACC_OWNER}' => $res['all']['CONNECTOR_ACCOUNT_HOLDER'],
                        '{ACC_NUMBER}' => $res['all']['CONNECTOR_ACCOUNT_NUMBER'],
                        '{ACC_BANKCODE}' => $res['all']['CONNECTOR_ACCOUNT_BANK'],
                        '{ACC_IBAN}' => $res['all']['CONNECTOR_ACCOUNT_IBAN'],
                        '{ACC_BIC}' => $res['all']['CONNECTOR_ACCOUNT_BIC'],
                        '{SHORTID}' => $res['all']['IDENTIFICATION_SHORTID']
                        );
    
        return strtr(constant('PREPAYMENT_TEXT'), $repl);
    }
}
