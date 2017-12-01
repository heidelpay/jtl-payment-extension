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

include_once(PFAD_ROOT . PFAD_INCLUDES_MODULES . 'ServerPaymentMethod.class.php');

class heidelpay_sbase extends ServerPaymentMethod
{
    public $response   = '';
    public $error      = '';

    //var $live_url   = 'https://ctpe.net/frontend/payment.prc';
  //var $demo_url   = 'https://test.ctpe.net/frontend/payment.prc';
  public $live_url = 'https://heidelpay.hpcgw.net/sgw/gtw';
    public $demo_url = 'https://test-heidelpay.hpcgw.net/sgw/gtw';

    public $availablePayments = array('CC','DD','DC','VA','OT','IV','PP','UA');
    public $pageURL = '';
    public $actualPaymethod = 'CC';
    public $url;

    public $importantPPFields = array(
    'PRESENTATION_AMOUNT',
    'PRESENTATION_CURRENCY',
    'CONNECTOR_ACCOUNT_COUNTRY',
    'CONNECTOR_ACCOUNT_HOLDER',
    'CONNECTOR_ACCOUNT_NUMBER',
    'CONNECTOR_ACCOUNT_BANK',
    'CONNECTOR_ACCOUNT_BIC',
    'IDENTIFICATION_SHORTID',
  );

    public function heidelpay_sbase()/*{{{*/
    {
        $this->pageURL = 'http://'.$_SERVER['HTTP_HOST'].'/';
        if ($this->isHTTPS()) {
            $this->pageURL = 'https://'.$_SERVER['HTTP_HOST'].'/';
        }
    }/*}}}*/

  public function prepareData($orderId, $amount, $currency, $payCode, $userData, $lang, $mode = 'DB', $capture = false, $uniqueId = null)/*{{{*/
  {
      global $Einstellungen, $DB, $smarty, $bestellung;

      if (class_exists('FB')) {
          FB::log($bestellung);
      }

      $payCode = strtoupper($payCode);
      $amount = sprintf('%1.2f', $amount);
      $currency = strtoupper($currency);
      $userData = $this->encodeData($userData);
      $this->pageURL = constant('HP'.$this->actualPaymethod.'_PLUGIN_URL');

      $parameters['SECURITY.SENDER']        = constant('HP'.$this->actualPaymethod.'_SECURITY_SENDER');
      $parameters['USER.LOGIN']             = constant('HP'.$this->actualPaymethod.'_USER_LOGIN');
      $parameters['USER.PWD']               = constant('HP'.$this->actualPaymethod.'_USER_PWD');
      $parameters['TRANSACTION.CHANNEL']    = constant('HP'.$this->actualPaymethod.'_TRANSACTION_CHANNEL');
      $parameters['TRANSACTION.MODE']       = constant('HP'.$this->actualPaymethod.'_TRANSACTION_MODE');
      $parameters['REQUEST.VERSION']        = "1.0";
      $parameters['IDENTIFICATION.TRANSACTIONID'] = $orderId;
      if ($capture) {
          $parameters['FRONTEND.ENABLED']     = "false";
          if (!empty($uniqueId)) {
              $parameters['ACCOUNT.REGISTRATION'] = $uniqueId;
          }
      } else {
          $parameters['FRONTEND.ENABLED']     = "true";
      }
      $parameters['FRONTEND.REDIRECT_TIME'] = "0";
      $parameters['FRONTEND.POPUP']         = "false";
      $parameters['FRONTEND.MODE']          = "DEFAULT";
      $parameters['FRONTEND.LANGUAGE']      = $lang;
      $parameters['FRONTEND.LANGUAGE_SELECTOR'] = "true";
      $parameters['FRONTEND.ONEPAGE']       = "true";
      $parameters['FRONTEND.NEXTTARGET']    = "top.location.href";
      $parameters['FRONTEND.CSS_PATH']      = $this->pageURL."heidelpay_style.css";

      $parameters['FRONTEND.BUTTON.1.NAME'] = 'PAY';
      $parameters['FRONTEND.BUTTON.1.TYPE'] = 'IMAGE';
      $parameters['FRONTEND.BUTTON.1.LINK'] = $this->pageURL.'gfx/pfeilRechts.gif';
      $parameters['FRONTEND.BUTTON.2.NAME'] = 'CANCEL';
      $parameters['FRONTEND.BUTTON.2.TYPE'] = 'IMAGE';
      $parameters['FRONTEND.BUTTON.2.LINK'] = $this->pageURL.'gfx/pfeilLinks.gif';
    
      if ($this->actualPaymethod == 'TP') {
          $parameters['CRITERION.THOMEPAY_EMB'] = "1";
        //$parameters['FRONTEND.HEIGHT']        = "700";
      } elseif ($this->actualPaymethod == 'SU') {
          //$parameters['FRONTEND.HEIGHT']        = "700";
      } elseif ($this->actualPaymethod == 'BS') {
          $parameters['PAYMENT.CODE']           = "IV.PA";
          $parameters['ACCOUNT.BRAND']          = "BILLSAFE";
          $parameters['FRONTEND.ENABLED']       = "false";

          $bsParams = $this->getBillsafeBasket($bestellung);
          if (class_exists('FB')) {
              FB::log($bsParams);
          }
          $parameters = array_merge($parameters, $bsParams);
      } elseif ($this->actualPaymethod == 'BP') {
          $parameters['PAYMENT.CODE']           = "PP.PA";
          $parameters['ACCOUNT.BRAND']          = "BARPAY";
          $parameters['FRONTEND.ENABLED']       = "false";
      } elseif ($this->actualPaymethod == 'MK') {
          $parameters['PAYMENT.CODE']           = "PC.PA";
          $parameters['ACCOUNT.BRAND']          = "MANGIRKART";
          $parameters['FRONTEND.ENABLED']       = "false";
      } elseif ($this->actualPaymethod == 'DD') {
          $sepaswitch =  constant('HP'.$this->actualPaymethod.'_SEPA_MODE');
      
          switch ($sepaswitch) {
        // account and bank no
        case 'classic':
            $parameters['FRONTEND.SEPA']                = 'NO';
            $parameters['FRONTEND.SEPASWITCH']    = 'NO';
        break;
        //  IBAN and BIC
        case 'iban':
            $parameters['FRONTEND.SEPA']                = 'YES';
            $parameters['FRONTEND.SEPASWITCH']    = 'NO';
        break;
        // both methodes separeted with an or
        case 'both':
            $parameters['FRONTEND.SEPA']               = 'YES';
            $parameters['FRONTEND.SEPASWITCH']    = 'YES';
        break;
        // both methodes with a selector
        case 'both_s':
            $parameters['FRONTEND.SEPA']               = 'NO';
            $parameters['FRONTEND.SEPASWITCH']    = 'YES';
        break;
      }
      }

      foreach ($this->availablePayments as $key=>$value) {
          if ($value != $payCode) {
              $parameters["FRONTEND.PM." . (string)($key + 1) . ".METHOD"] = $value;
              $parameters["FRONTEND.PM." . (string)($key + 1) . ".ENABLED"] = "false";
          }
      }

    // Wenn der Payment Code noch nicht gesetzt wurde
        if (empty($parameters['PAYMENT.CODE'])) {
            $parameters['PAYMENT.CODE']           = $payCode.".".$mode;
        }
      $parameters['FRONTEND.RESPONSE_URL']  = $this->pageURL."heidelpay_response.php?".session_name().'='.session_id();
      $parameters['CRITERION.RESPONSE_URL'] = $parameters['FRONTEND.RESPONSE_URL'];
      if (strpos($_SERVER['REMOTE_ADDR'], '127.0.0') !== false) {
          $parameters['FRONTEND.RESPONSE_URL']  = "http://demoshops.heidelpay.de/resp/jtl307/heidelpay_response.php?".session_name().'='.session_id();
      }

      $parameters['NAME.GIVEN']                            = $userData['firstname'];
      $parameters['NAME.FAMILY']                            = $userData['lastname'];
      $parameters['NAME.COMPANY']                    = $userData['company'];
      $parameters['ADDRESS.STREET']                    = $userData['street'];
      $parameters['ADDRESS.ZIP']                            = $userData['zip'];
      $parameters['ADDRESS.CITY']                        = $userData['city'];
      $parameters['ADDRESS.COUNTRY']                = $userData['country'];
      $parameters['CONTACT.EMAIL']                        = $userData['email'];
      $parameters['PRESENTATION.AMOUNT']        = $amount; // 99.00
    $parameters['PRESENTATION.CURRENCY']    = $currency; // EUR
    $parameters['ACCOUNT.COUNTRY']                = $userData['country'];

      $parameters['SHOP.TYPE'] = 'JTL '.JTL_VERSION;
      $parameters['SHOPMODULE.VERSION'] = 'Standard V'.$this->getPluginVersion();
      return $parameters;
  }/*}}}*/

  public function getBillsafeBasket($order)/*{{{*/
  {
      $items = $order->Positionen;
      if (class_exists('FB')) {
          FB::log($items);
      }
      $i = 0;
      if ($items) {
          foreach ($items as $id => $item) {
              $i++;
              $prefix = 'CRITERION.POS_'.sprintf('%02d', $i);
              if ($item->nPosTyp == C_WARENKORBPOS_TYP_ARTIKEL) { // Artikel
          $parameters[$prefix.'.POSITION']                = $i;
                  $parameters[$prefix.'.QUANTITY']                = (int)$item->nAnzahl;
                  $parameters[$prefix.'.UNIT']                        = 'Stk.'; // Liter oder so
          //if ($_SESSION['customers_status']['customers_status_show_price_tax'] == '0') {
          //$parameters[$prefix.'.AMOUNT_UNIT'] 		= (int)($item->fPreisEinzelNetto*100);
          //$parameters[$prefix.'.AMOUNT'] 		      = (int)($item->fPreisEinzelNetto*100)*$item->nAnzahl;
          //} else {
          $parameters[$prefix.'.AMOUNT_UNIT_GROSS'] = (int)round((($item->fPreisEinzelNetto + (($item->fPreisEinzelNetto/100)*$item->fMwSt)) * 100));
                  $parameters[$prefix.'.AMOUNT_GROSS']          = (int)round((($item->fPreisEinzelNetto + (($item->fPreisEinzelNetto/100)*$item->fMwSt)) * 100) * $item->nAnzahl);
          //}
          //$parameters[$prefix.'.AMOUNT_UNIT_GROSS'] 		= round($item['price']*100) + round($item['price']*$item['tax']);
          //$parameters[$prefix.'.AMOUNT_GROSS'] 		      = round($item['final_price']*100) + round($item['final_price']*$item['tax']);
          $parameters[$prefix.'.TEXT']                        = $item->cName;
          //$parameters[$prefix.'.COL1'] 						= 'SKU:'.$item->getSku();
          //$parameters[$prefix.'.COL2'] 						= '';
          //$parameters[$prefix.'.COL3'] 						= '';
          //$parameters[$prefix.'.COL4'] 						= '';
          $parameters[$prefix.'.ARTICLE_NUMBER']    = $item->kArtikel;
                  $parameters[$prefix.'.PERCENT_VAT']        = sprintf('%1.2f', $item->fMwSt);
                  $parameters[$prefix.'.ARTICLE_TYPE']        = 'goods'; // "goods" (Versandartikel), "shipment" (Versandkosten) oder "voucher" (Gutschein/Rabatt)
              } elseif ($item->nPosTyp == C_WARENKORBPOS_TYP_VERSANDPOS) { // Shipping
          $parameters[$prefix.'.POSITION']                = $i;
                  $parameters[$prefix.'.QUANTITY']                = '1';
                  $parameters[$prefix.'.UNIT']                        = 'Stk.'; // Liter oder so
          $parameters[$prefix.'.AMOUNT_UNIT']        = sprintf('%1.2f', $item->fPreisEinzelNetto)*100;
                  $parameters[$prefix.'.AMOUNT']              = sprintf('%1.2f', $item->fPreisEinzelNetto)*100*$item->nAnzahl;
                  $parameters[$prefix.'.TEXT']                        = $item->cName;
                  $parameters[$prefix.'.ARTICLE_NUMBER']    = '0';
                  $parameters[$prefix.'.PERCENT_VAT']        = sprintf('%1.2f', $item->fMwSt);
                  $parameters[$prefix.'.ARTICLE_TYPE']        = 'shipment'; // "goods" (Versandartikel), "shipment" (Versandkosten) oder "voucher" (Gutschein/Rabatt)
              } else { // Voucher
          $parameters[$prefix.'.POSITION']                = $i;
                  $parameters[$prefix.'.QUANTITY']                = '1';
                  $parameters[$prefix.'.UNIT']                        = 'Stk.'; // Liter oder so
          $parameters[$prefix.'.AMOUNT_UNIT']        = sprintf('%1.2f', $item->fPreisEinzelNetto)*100;
                  $parameters[$prefix.'.AMOUNT']              = sprintf('%1.2f', $item->fPreisEinzelNetto)*100*$item->nAnzahl;
                  $parameters[$prefix.'.TEXT']                        = $item->cName;
                  $parameters[$prefix.'.ARTICLE_NUMBER']    = '0';
                  $parameters[$prefix.'.PERCENT_VAT']        = sprintf('%1.2f', $item->fMwSt);
                  $parameters[$prefix.'.ARTICLE_TYPE']        = 'voucher'; // "goods" (Versandartikel), "shipment" (Versandkosten) oder "voucher" (Gutschein/Rabatt)
              }
          }
      }

    //echo '<pre>'.print_r($parameters, 1).'</pre>';
    //exit();

    return $parameters;
  }/*}}}*/

  public function getPluginVersion()/*{{{*/
  {
      global $db;
      $sql = 'SELECT * FROM `tplugin` WHERE `cPluginID` = "heidelpay_premium" ';
      $res = $GLOBALS["DB"]->executeQuery($sql, 1);
      return $res->nXMLVersion;
  }/*}}}*/

  public function encodeData($data)/*{{{*/
  {
      $tmp = array();
      foreach ($data as $k => $v) {
          $tmp[$k] = $v;
          if (!$this->isUTF8($v)) {
              $tmp[$k] = utf8_encode($v);
          }
      }
      return $tmp;
  }/*}}}*/

  public function isUTF8($string)/*{{{*/
  {
      if (is_array($string)) {
          $enc = implode('', $string);
          return @!((ord($enc[0]) != 239) && (ord($enc[1]) != 187) && (ord($enc[2]) != 191));
      } else {
          return (utf8_encode(utf8_decode($string)) == $string);
      }
  }/*}}}*/

  public function isHTTPS()/*{{{*/
  {
      if (strpos($_SERVER['HTTP_HOST'], '.local') === false) {
          if (!isset($_SERVER['HTTPS']) || (strtolower($_SERVER['HTTPS']) != 'on' && $_SERVER['HTTPS'] != '1')) {
              return false;
          }
      }
      return true;
  }/*}}}*/

  public function doRequest($data)/*{{{*/
  {
      $url = $this->demo_url;
      if (constant('HP'.$this->actualPaymethod.'_TRANSACTION_MODE') == 'LIVE') {
          $url = $this->live_url;
      }
      $this->url = $url;

    // Erstellen des Strings f�r die Daten�bermittlung
    foreach (array_keys($data) as $key) {
        $data[$key] = utf8_decode($data[$key]);
        $$key .= $data[$key];
        $$key = urlencode($$key);
        $$key .= "&";
        $var = strtoupper($key);
        $value = $$key;
        $result .= "$var=$value";
    }
      $strPOST = stripslashes($result);
    
    // pr�fen ob CURL existiert
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $strPOST);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "php ctpepost");

        $this->response     = curl_exec($ch);
        $this->error        = curl_error($ch);
        curl_close($ch);

        $res = $this->response;
        if (!$this->response && $this->error) {
            $msg = urlencode('Curl Fehler');
            $res = 'status=FAIL&msg='.$this->error;
        }
    } else {
        $msg = urlencode('Curl Fehler');
        $res = 'status=FAIL&&msg='.$msg;
    }

      return $res;
  }/*}}}*/

  public function parseResult($curlresultURL)/*{{{*/
  {
      $r_arr=explode("&", $curlresultURL);
      foreach ($r_arr as $buf) {
          $temp=urldecode($buf);
          $temp=split("=", $temp, 2);
          $postatt=$temp[0];
          $postvar=$temp[1];
          $returnvalue[$postatt]=$postvar;
      }
      $processingresult = $returnvalue['POST.VALIDATION'];
      $redirectURL = trim($returnvalue['FRONTEND.REDIRECT_URL']);

      return array('result' => $processingresult, 'url' => $redirectURL, 'all' => $returnvalue);
  }/*}}}*/

  /*
   * 1 - single fetched object
   * 2 - array of fetched objects
   * 3 - affected rows
   * 8 - single fetched assoc array
   * 9 - array of fetched assoc arrays
   * 10 - result of query
   * x - bool, if query successful
   */
  public function setStatus($type, $orderId, $amount = null)/*{{{*/
  {
      $status = '';
      switch ($type) {
    case 'SUCCESS':
      $status = BESTELLUNG_STATUS_BEZAHLT;
      break;
    case 'PENDING':
      $status = BESTELLUNG_STATUS_IN_BEARBEITUNG;
      break;
    case 'CANCEL':
      $status = BESTELLUNG_STATUS_STORNO;
      break;
    case 'FAILED':
      $status = BESTELLUNG_STATUS_IN_BEARBEITUNG;
      break;
    }
      if ($status != '') {
          $sql = 'UPDATE `tbestellung` 
        SET `dBezahltDatum` = NOW(),
          `cStatus` = "'.$status.'",
          `cAbgeholt` = "N"
          WHERE `cBestellNr` = "'.addslashes($orderId).'"';
          $GLOBALS["DB"]->executeQuery($sql, 3);
          if ($status == BESTELLUNG_STATUS_BEZAHLT) {
              $sql = 'SELECT * FROM `tbestellung` WHERE `cBestellNr` = "'.addslashes($orderId).'" ';
              $res = $GLOBALS["DB"]->executeQuery($sql, 1);
        #echo '<pre>'.print_r($res, 1).'</pre>';
        $sql = 'INSERT INTO `tzahlungseingang` 
          SET `kBestellung` = "'.addslashes($res->kBestellung).'",
            `cZahlungsanbieter` = "'.addslashes($res->cZahlungsartName).'",
            `fBetrag` = "'.addslashes($amount).'",
            `cAbgeholt` = "N"
            ';
              $GLOBALS["DB"]->executeQuery($sql, 4);
          }
      }
      return true;
  }/*}}}*/
}
