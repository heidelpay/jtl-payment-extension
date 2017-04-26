<?php

/*
 * SUMMARY
 *
 * DESC
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author Ronja Wann
 * @category JTL
 */
include_once(PFAD_ROOT . PFAD_INCLUDES_MODULES . 'ServerPaymentMethod.class.php');
require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/vendor/autoload.php';


/*
 * Heidelpay
 */

class heidelpay_standard extends ServerPaymentMethod
{
    public $paymenObject = null;
    public $pluginName = "heidelpay_standard";


    public function init($nAgainCheckout = 0)
    {
        parent::init($nAgainCheckout);

        $this->name = 'Heidelpay';
        $this->caption = 'Heidelpay';


        $sql = "SELECT * FROM `tzahlungsart` WHERE `cModulId` = '{$this->moduleID}'";
        $this->info = $GLOBALS ["DB"]->executeQuery($sql, 1);
    }


    /**
     * Checks if string is UTF8
     *
     * @param $string
     * @return bool
     */
    public function isUTF8($string)
    {
        return (utf8_encode(utf8_decode($string)) == $string);
    }

    /**
     * Encodes data to UTF8
     *
     * @param $data
     * @return insert $data in UTF8
     */
    public function encodeData($data)
    {
        foreach ($data as $k => $v) {
            if (!$this->isUTF8($v)) {
                $data [$k] = utf8_encode($v);
            }
        }
        return $data;
    }


    /**
     * Gets prefix of current payment method
     *
     * @param $oPlugin
     * @param $currentPaymentMethod
     * @return string current payment method prefix
     */
    public function getCurrentPaymentMethodPrefix($oPlugin, $currentPaymentMethod)
    {
        $payCode = strtolower($oPlugin->oPluginEinstellungAssoc_arr [$currentPaymentMethod . '_paycode']);
        return strtoupper('HP' . $payCode);
    }

    /**
     * Sets payment object depending on chosen payment method
     *
     * @param $paymentMethodPrefix
     */
    public function setPaymentObject($paymentMethodPrefix)
    {
        switch ($paymentMethodPrefix) {

            case 'HPCC':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\CreditCardPaymentMethod();
                break;
            case 'HPDC':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\DebitCardPaymentMethod();
                break;
            case 'HPDD':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\DirectDebitPaymentMethod();
                break;
            case 'HPSU':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\SofortPaymentMethod();
                break;
            case 'HPGP':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\GiropayPaymentMethod();
                break;
            case 'HPIDL':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\IDealPaymentMethod();
                break;
            case 'HPEPS':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\EPSPaymentMethod();
                break;
            case 'HPVA':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\PayPalPaymentMethod();
                break;
            case 'HPP24':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\Przelewy24PaymentMethod();
                break;
            case 'HPPFC':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\PostFinanceCardPaymentMethod();
                break;
            case 'HPPFE':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\PostFinanceEFinancePaymentMethod();
                break;
            case 'HPPP':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\PrepaymentPaymentMethod();
                break;
            case 'HPSA':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\SantanderInvoicePaymentMethod();
                break;
            case 'HPDDPG':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\DirectDebitB2CSecuredPaymentMethod();
                break;
            case 'HPIVPG':
                $this->paymenObject = new Heidelpay\PhpApi\PaymentMethods\InvoiceB2CSecuredPaymentMethod();
                break;
        }
    }

    /**
     * Sets payment template depending on chosen payment method
     *
     * @param $paymentMethodPrefix
     * @param $oPlugin
     */
    public function setPaymentTemplate($paymentMethodPrefix, $oPlugin)
    {
        global $smarty;

        $smarty->assign('pay_button_label', $this->getPayButtonLabel());
        $smarty->assign('paytext', utf8_decode($this->getPayText()));

        setlocale(LC_TIME, $this->getLanguageCode());

        switch ($paymentMethodPrefix) {
            case 'HPCC':
            case 'HPDC':
            case 'HPDD':
                $smarty->assign('holder_label', $this->getHolderLabel());
                $smarty->assign('holder', $_SESSION['Kunde']->cVorname . ' ' . $_SESSION['Kunde']->cNachname);
                $smarty->assign('action_url', $this->paymenObject->getResponse()->getPaymentFormUrl());
                break;
            case 'HPDDPG':
            case 'HPIVPG':
                $smarty->assign('holder_label', $this->getHolderLabel());
                $smarty->assign('birthdate_label', $this->getBirthdateLabel());
                $smarty->assign('salutation', $this->getSalutationArray());
                $smarty->assign('salutation_pre', $this->getSalutation());
                $smarty->assign('holder', $_SESSION['Kunde']->cVorname . ' ' . $_SESSION['Kunde']->cNachname);
                $smarty->assign('is_PG', true);
                $smarty->assign('birthdate', str_replace('.', '-', $_SESSION['Kunde']->dGeburtstag));
                $smarty->assign('action_url', $this->paymenObject->getResponse()->getPaymentFormUrl());
                break;
            case 'HPIDL':
            case 'HPEPS':
                $smarty->assign('action_url', $this->paymenObject->getResponse()->getPaymentFormUrl());
                $smarty->assign('account_country', $this->paymenObject->getResponse()->getConfig()->getBankCountry());
                $smarty->assign('account_bankname', $this->paymenObject->getResponse()->getConfig()->getBrands());
                break;
            case 'HPSA':
                $smarty->assign('birthdate_label', $this->getBirthdateLabel());
                $smarty->assign('privatepolicy_label', $this->getPrivatePolicyLabel());
                $smarty->assign('action_url', $this->paymenObject->getResponse()->getPaymentFormUrl());
                $smarty->assign('salutation', $this->getSalutationArray());
                $smarty->assign('salutation_pre', $this->getSalutation());
                $smarty->assign('holder', $_SESSION['Kunde']->cVorname . ' ' . $_SESSION['Kunde']->cNachname);
                $smarty->assign('birthdate', str_replace('.', '-', $_SESSION['Kunde']->dGeburtstag));
                $optinText = $this->paymenObject->getResponse()->getConfig()->getOptinText();
                $smarty->assign('optin', utf8_decode($optinText['optin']));
                $smarty->assign('privacy_policy', utf8_decode($optinText['privacy_policy']));
                break;


            default:
                $this->redirect($this->paymenObject->getResponse()->getPaymentFormUrl());

        }
    }

    /**
     * Gets customer data from current session
     * sets customer address on shipping address in case of PayPal for PayPal buyer protection
     *
     * @param $oPlugin
     * @return array with user data (name, address and mail)
     */
    public function getCustomerData($oPlugin, $currentPaymentMethod)
    {
        $payCode = $this->getCurrentPaymentMethodPrefix($oPlugin, $currentPaymentMethod);

        //PayPal Case
        if ($payCode == 'HPVA') {
            $user = $_SESSION ['Lieferadresse'];
            $mail = $_SESSION ['Kunde'];
            $userStreet = $user->cStrasse . ' ' . $user->cHausnummer;
            $userData = array((empty($user->cVorname)) ? null : $user->cVorname, (empty($user->cNachname)) ? null : $user->cNachname, (empty($user->cFirma)) ? null : $user->cFirma, (empty($user->kKunde)) ? null : $user->kKunde, (empty($userStreet)) ? null : $userStreet, (empty($user->cBundesland)) ? null : $user->cBundesland, (empty($user->cPLZ)) ? null : $user->cPLZ, (empty($user->cOrt)) ? null : $user->cOrt, (empty($user->cLand)) ? null : $user->cLand, (empty($mail->cMail)) ? null : $mail->cMail);
        } else {
            $user = $_SESSION ['Kunde'];
            $userStreet = $user->cStrasse . ' ' . $user->cHausnummer;
            $userData = array((empty($user->cVorname)) ? null : $user->cVorname, (empty($user->cNachname)) ? null : $user->cNachname, (empty($user->cFirma)) ? null : $user->cFirma, (empty($user->kKunde)) ? null : $user->kKunde, (empty($userStreet)) ? null : $userStreet, (empty($user->cBundesland)) ? null : $user->cBundesland, (empty($user->cPLZ)) ? null : $user->cPLZ, (empty($user->cOrt)) ? null : $user->cOrt, (empty($user->cLand)) ? null : $user->cLand, (empty($user->cMail)) ? null : $user->cMail);
        }

        return $this->encodeData($userData);
    }

    /**
     * Gets order information of current session
     *
     * @return array order information
     */
    public function getBasketData($order, $oPlugin)
    {
        $orderId = $order->cBestellNr;
        if (empty($orderId)) {
            $orderId = baueBestellnummer();
        }

        $amount = $order->fGesamtsummeKundenwaehrung; // In Kunden Währung
        if (empty($amount)) {
            $amount = $_SESSION ["Warenkorb"]->gibGesamtsummeWaren(1);
        }

        $amount = sprintf('%1.2f', $amount);


        $basketData = array(

            $orderId, $amount, $_SESSION ['Waehrung']->cISO, $oPlugin->oPluginEinstellungAssoc_arr['secret']

        );

        return $basketData;
    }

    /**
     * Checks if Sandbox-Mode active or not
     *
     * @param $oPlugin
     * @return bool true = sandbox mode active, false = live mode active (productive system)
     */
    public function getTransactionMode($oPlugin, $currentPaymentMethod)
    {
        if ($oPlugin->oPluginEinstellungAssoc_arr [$currentPaymentMethod . '_transmode'] == 'LIVE') {
            return false;
        }

        return true;
    }


    /**
     * gets salutation from session for payment
     *
     * @return string 'MR' or 'MRS' depending on the salutation in session
     */
    public function getSalutation()
    {
        if ($_SESSION['Kunde']->cAnrede == 'm') {
            $salutation = 'MR';
        } else {
            $salutation = 'MRS';
        }

        return $salutation;
    }

    public function setLocal()
    {
        if ($_SESSION ['cISOSprache'] == 'ger') {
            setlocale(LC_ALL, 'de_DE');
        } else {
            setlocale(LC_ALL, 'en_US');
        }
    }


    /**
     * creates salutation array for template depending on session language
     *
     * @return array with salutation options
     */
    public function getSalutationArray()
    {
        if ($_SESSION ['cISOSprache'] == 'ger') {
            $salutationArray = array('MR' => 'Herr', 'MRS' => 'Frau');
        } else {
            $salutationArray = array('MR' => 'Mr', 'MRS' => 'Mrs');
        }

        return $salutationArray;
    }


    public function getPayButtonLabel()
    {
        if ($_SESSION ['cISOSprache'] == 'ger') {
            $payButtonLabel = 'Jetzt zahlen';
        } else {
            $payButtonLabel = 'Pay now';
        }

        return $payButtonLabel;
    }

    public function getHolderLabel()
    {
        if ($_SESSION ['cISOSprache'] == 'ger') {
            $holderLabel = 'Kontoinhaber';
        } else {
            $holderLabel = 'Holder';
        }

        return $holderLabel;
    }

    public function getBirthdateLabel()
    {
        if ($_SESSION ['cISOSprache'] == 'ger') {
            $birthdateLabel = 'Geburtsdatum';
        } else {
            $birthdateLabel = 'Birthdate';
        }

        return $birthdateLabel;
    }

    public function getPayText()
    {
        if ($_SESSION ['cISOSprache'] == 'ger') {
            $payText = 'Bitte vervollständigen Sie die unten aufgeführten Daten und schließen Sie den Bestellprozess ab.';
        } else {
            $payText = 'Please complete the following data and complete the order process.';
        }

        return $payText;
    }

    public function getPrivatePolicyLabel($oPlugin)
    {
        if ($_SESSION ['cISOSprache'] == 'ger') {
            $privatePolicyLabel = $oPlugin->oPluginSprachvariable_arr['0']->oPluginSprachvariableSprache_arr['GER'];
        } else {
            $privatePolicyLabel = $oPlugin->oPluginSprachvariable_arr['0']->oPluginSprachvariableSprache_arr['ENG'];
        }

        return $privatePolicyLabel;
    }

    public function getBookingMode($oPlugin, $currentPaymentMethod)
    {
        $bookingMode = $oPlugin->oPluginEinstellungAssoc_arr [$currentPaymentMethod . '_bookingmode'];
        return $bookingMode;
    }

    public function getPaymentFrameOrigin()
    {
        $parse_url = parse_url(Shop::getURL());
        $paymentFrameOrigin = $parse_url['scheme'] . '://' . $parse_url['host'];
        return $paymentFrameOrigin;
    }

    /**
     * @return string
     */
    public function getLanguageCode()
    {
        $language = $_SESSION ['cISOSprache'] == 'ger' ? 'DE' : 'EN';

        return $language;
    }

    // sets Short-ID as comment in Database
    public function setShortId($shortId, $orderId)
    {
        (preg_match('/\d{4}[.]\d{4}[.]\d{4}/', $shortId)) ? $shortId : false;

        if (!is_numeric($orderId)) {
            return false;
        }

        $sql = 'UPDATE `tbestellung`
        SET `cKommentar` = ?
          WHERE `cBestellNr` = ?';
        $GLOBALS ["DB"]->executeQueryPrepared($sql, array($shortId, $orderId), 3);
    }

    public function setPayInfo($post, $orderId)
    {
        $bookingtext= 'Bitte überweisen Sie uns den Betrag von '.$post['PRESENTATION_AMOUNT'] .' '.$post['PRESENTATION_CURRENCY'].' nach erhalt der Ware auf folgendes Konto:
        
  Kontoinhaber: '.$post['CONNECTOR_ACCOUNT_HOLDER'].'
  IBAN: '.$post['CONNECTOR_ACCOUNT_IBAN'].'
  BIC: '.$post['CONNECTOR_ACCOUNT_BIC'].'
  
  Geben Sie als Verwendungszweck bitte ausschließlich folgende Identifikationsnummer an:
  '.$post['IDENTIFICATION_SHORTID'];



        $sql = 'UPDATE `tbestellung`
        SET `cKommentar` = ?
          WHERE `cBestellNr` = ?';
        $GLOBALS ["DB"]->executeQueryPrepared($sql, array(utf8_decode($bookingtext), $orderId), 3);
    }

// generates Hash for creterion secret with secretPhrase and orderID
    public function getHash($secret, $orderId)
    {
        return hash('sha256', $secret . $orderId);
    }


    public function getPlugin($currentPaymentMethod)
    {
        $kPlugin = gibkPluginAuscModulId($currentPaymentMethod);
        if ($kPlugin > 0) {
            $oPlugin = new Plugin($kPlugin);
        } else {
            return false;
        }
        $actZVPrefix = 'kPlugin_' . $oPlugin->kPlugin . '_';
        $actZV = str_replace($actZVPrefix, '', $currentPaymentMethod);

        return $oPlugin;
    }




    /**
     * Redirects customer
     */
    public function redirect($url)
    {
        header('Location: ' . $url);
    }

    /**
     * @param $order
     * @return bool
     */
    public function preparePaymentProcess($order)
    {
        global $bestellung;


        $currentPaymentMethod = $_SESSION ['Zahlungsart']->cModulId;
        if (empty($currentPaymentMethod)) {
            $currentPaymentMethod = $bestellung->Zahlungsart->cModulId;
        }

        $this->getPlugin($currentPaymentMethod);


        $hash = $this->generateHash($order);
        if (property_exists($order, 'cId')) {
            $hash = $order->cId;
        }

        $this->moduleID = $currentPaymentMethod;
        $this->init(0);

        $notifyURL = $this->getNotificationURL($hash);


        $oPlugin = $this->getPlugin($currentPaymentMethod);

        $paymentMethodPrefix = $this->getCurrentPaymentMethodPrefix($oPlugin, $currentPaymentMethod);


        $this->setPaymentObject($paymentMethodPrefix);


        $this->paymenObject->getRequest()->authentification($oPlugin->oPluginEinstellungAssoc_arr ['sender'], $oPlugin->oPluginEinstellungAssoc_arr ['user'], $oPlugin->oPluginEinstellungAssoc_arr ['pass'], $oPlugin->oPluginEinstellungAssoc_arr [$currentPaymentMethod . '_channel'], $this->getTransactionMode($oPlugin, $currentPaymentMethod));

        $this->paymenObject->getRequest()->customerAddress(...$this->getCustomerData($oPlugin, $currentPaymentMethod));
        $this->paymenObject->getRequest()->basketData(...$this->getBasketData($order, $oPlugin));

        $this->paymenObject->getRequest()->async($this->getLanguageCode(), $notifyURL);

        $this->paymenObject->getRequest()->getCriterion()->set('PAYMETHOD', $currentPaymentMethod);


        switch ($paymentMethodPrefix) {
            case 'HPCC':
            case 'HPDC':
                if ($this->getBookingMode($oPlugin, $currentPaymentMethod) == 'DB') {
                    $this->paymenObject->debit($this->getPaymentFrameOrigin(), 'FALSE');
                } else {
                    $this->paymenObject->authorize($this->getPaymentFrameOrigin(), 'FALSE');
                }
                break;
            case 'HPDD':
                $this->paymenObject->debit();
                break;
            case 'HPVA':
                if ($this->getBookingMode($oPlugin, $currentPaymentMethod) == 'DB') {
                    $this->paymenObject->debit();
                    break;
                }
            default:
                $this->paymenObject->authorize();
                break;


        }

        if ($this->paymenObject->getResponse()->isError()) {
            $errorCode = $this->paymenObject->getResponse()->getError();
            $this->redirect('bestellvorgang.php?heidelpayErrorCode=' . $errorCode['code']);
            return;
        }

        $this->setLocal();
        $this->setPaymentTemplate($paymentMethodPrefix, $oPlugin);
    }

    public function handleNotification($order, $paymentHash, $post)
    {
        global $bestellung;

        $this->init();


        $HeidelpayResponse = new  Heidelpay\PhpApi\Response($_POST);

        $oPlugin = $this->getPlugin($_POST['CRITERION_PAYMETHOD']);

        $secretPass = $oPlugin->oPluginEinstellungAssoc_arr ['secret'];

        $identificationTransactionId = $HeidelpayResponse->getIdentification()->getTransactionId();


        try {
            $HeidelpayResponse->verifySecurityHash($secretPass, $identificationTransactionId);
        } catch (\Exception $e) {
            /* If the verification does not match this can mean some kind of manipulation or
             * miss configuration. So you can log $e->getMessage() for debugging.*/

            $callers = debug_backtrace();
            Jtllog::write("Heidelpay - " . $callers [0] ['function'] . ": Invalid response hash from " . $_SERVER ['REMOTE_ADDR'] . ", suspecting manipulation", 2, false, 'Notify');
            exit();

            return;
        }

        $orderID = (int)preg_replace('/_\d*/', '', $_POST['IDENTIFICATION_TRANSACTIONID']);

        if ($HeidelpayResponse->isSuccess()) {

            /* save order and transaction result to your database */


            unset($_SESSION ['heidelpayLastError']); // damit ggf. vorherige Fehler geloescht werden
            if ($this->verifyNotification($order, $paymentHash, $post)) {


                /* zusatz Email mit Mandatsreferenz-ID für Lastschrift */
                $firma = $GLOBALS ["DB"]->executeQuery("select * from tfirma", 1);
                $einstellungen = getEinstellungen(array(CONF_EMAILS));
                $payCode = explode('.', $post ['PAYMENT_CODE']);


                if ((strtoupper($payCode [0]) == 'DD') && (!isset($post ['TRANSACTION_SOURCE']))) {
                    $language = $_SESSION ['cISOSprache'] == 'ger' ? 'DE' : 'EN';
                    if ($language == 'DE') {
                        include_once(PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/112/paymentmethod/template/heidelpay_ddMail_de.tpl');
                    } else {
                        include_once(PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/112/paymentmethod/template/heidelpay_ddMail_en.tpl');
                    }
                    $repl = array('{ACC_IBAN}' => $post ['ACCOUNT_IBAN'], '{ACC_BIC}' => $post ['ACCOUNT_BIC'], '{ACC_IDENT}' => $post ['ACCOUNT_IDENTIFICATION'], '{AMOUNT}' => $post ['PRESENTATION_AMOUNT'], '{CURRENCY}' => $post ['PRESENTATION_CURRENCY'], '{HOLDER}' => $post ['ACCOUNT_HOLDER']);
                    if ((isset($post ['IDENTIFICATION_CREDITOR_ID']) && ($post ['IDENTIFICATION_CREDITOR_ID'] != ''))) {
                        $repl ['{IDENT_CREDITOR}'] = $post ['IDENTIFICATION_CREDITOR_ID'];
                    } else {
                        $repl ['{IDENT_CREDITOR}'] = '-';
                    }
                    mail($order->oRechnungsadresse->cMail, constant('DD_MAIL_SUBJECT'), strtr(constant('DD_MAIL_TEXT'), $repl), constant('DD_MAIL_HEADERS'));
                } elseif ((strtoupper($payCode [0]) == 'PP') && (!isset($post ['TRANSACTION_SOURCE']))) {
                    $language = $_SESSION ['cISOSprache'] == 'ger' ? 'DE' : 'EN';
                    if ($language == 'DE') {
                        include_once(PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/112/paymentmethod/template/heidelpay_ppMail_de.tpl');
                    } else {
                        include_once(PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/112/paymentmethod/template/heidelpay_ppMail_en.tpl');
                    }
                    $repl = array('{ACC_IBAN}' => $post ['CONNECTOR_ACCOUNT_IBAN'], '{ACC_BIC}' => $post ['CONNECTOR_ACCOUNT_BIC'], '{ACC_OWNER}' => $post ['CONNECTOR_ACCOUNT_HOLDER'], '{AMOUNT}' => $post ['PRESENTATION_AMOUNT'], '{CURRENCY}' => $post ['PRESENTATION_CURRENCY'], '{USAGE}' => $post ['IDENTIFICATION_SHORTID']);
                    mail($order->oRechnungsadresse->cMail, constant('PP_MAIL_SUBJECT'), strtr(constant('PP_MAIL_TEXT'), $repl), constant('PP_MAIL_HEADERS'));
                } elseif ((strtoupper($payCode [0]) == 'IV') && (!isset($post ['TRANSACTION_SOURCE']))) {
                    $this->setPayInfo($post, $order->cBestellNr);
                }
                $incomingPayment->fBetrag = number_format($order->fGesamtsummeKundenwaehrung, 2, '.', '');
                $incomingPayment->cISO = $order->Waehrung->cISO;
                $this->addIncomingPayment($order, $incomingPayment);

                if (strtoupper($payCode [0]) != 'PP' and strtoupper($payCode [0]) != 'IV') { // Nur wenn nicht Vorkasse, Billsafe od. Rechnung
                    try {
                        $this->setOrderStatusToPaid($order);
                    } catch (Exception $e) {
                        $e = 'Update order status failed on order: ' . $order . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() . ' with message: ' . $e->getMessage();
                        $logData = array();
                        $logData ['module'] = 'Heidelpay Standard';
                        $logData ['order'] = $order;
                        $logData ['error_msg'] = $e;
                        Jtllog::write($logData, 1, false);
                    }
                    try {
                        $this->sendConfirmationMail($order);
                    } catch (Exception $e) {
                        $e = 'Update order status failed on order: ' . $order . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() . ' with message: ' . $e->getMessage();
                        $logDaten = array();
                        $logDaten ['module'] = 'Heidelpay Standard';
                        $logDaten ['order'] = $order;
                        $logDaten ['error_msg'] = $e;
                        Jtllog::write($logData, 1, false);
                    }
                }
                $this->updateNotificationID($order->kBestellung, $post ['IDENTIFICATION_UNIQUEID']);
            }

            /* redirect customer to success page */


            echo $this->getReturnURL($order);

            /*save order */
        } elseif ($HeidelpayResponse->isError()) {
            $error = $HeidelpayResponse->getError();


            if ($post ['FRONTEND_REQUEST_CANCELLED'] != 'true') {
                $callers = debug_backtrace();
                Jtllog::write("Heidelpay - " . $callers [0] ['function'] . ": Order " . $post ['IDENTIFICATION_TRANSACTIONID'] . " (Short-ID: " . $post ['IDENTIFICATION_SHORTID'] . ") failed because " . $post ['PROCESSING_RETURN'] . " (" . $post ['PROCESSING_RETURN_CODE'] . ")", 2, false, 'Notify');
            }


            if ($post ['FRONTEND_REQUEST_CANCELLED'] != 'true') { // write Short-ID and Unique-ID in comment when txn not cancled by user
                $this->setShortId('Short-ID: ' . $post ['IDENTIFICATION_SHORTID'] . ', Unique-ID: ' . $post ['IDENTIFICATION_UNIQUEID'], $post ['IDENTIFICATION_TRANSACTIONID']);
            }


            echo $this->getReturnURL($order) . '&hperror=' . $error['code'];
        } elseif ($HeidelpayResponse->isPending()) {
            echo $this->getReturnURL($order);
        }
    }

    /**
     *
     * @return boolean
     * @param Bestellung $order
     * @param array $post
     */
    public function verifyNotification($order, $paymentHash, $post)
    {
        extract($post);

        if ($CLEARING_AMOUNT != number_format($order->fGesamtsummeKundenwaehrung, 2, '.', '')) {
            return false;
        }

        if ($CLEARING_CURRENCY != $order->Waehrung->cISO) {
            return false;
        }
        return true;
    }

    public function finalizeOrder($order, $hash, $post)
    {
        global $cEditZahlungHinweis;
        extract($post);

        if ($PROCESSING_RESULT == "ACK") {
            return true;
        } else {
            $cEditZahlungHinweis = $_POST ['PROCESSING_RETURN'];
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
    } /* }}} */
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

        $repl = array('{CURRENCY}' => $res ['all'] ['PRESENTATION_CURRENCY'], '{AMOUNT}' => $res ['all'] ['PRESENTATION_AMOUNT'], '{ACC_COUNTRY}' => $res ['all'] ['CONNECTOR_ACCOUNT_COUNTRY'], '{ACC_OWNER}' => $res ['all'] ['CONNECTOR_ACCOUNT_HOLDER'], '{ACC_NUMBER}' => $res ['all'] ['CONNECTOR_ACCOUNT_NUMBER'], '{ACC_BANKCODE}' => $res ['all'] ['CONNECTOR_ACCOUNT_BANK'], '{ACC_IBAN}' => $res ['all'] ['CONNECTOR_ACCOUNT_IBAN'], '{ACC_BIC}' => $res ['all'] ['CONNECTOR_ACCOUNT_BIC'], '{SHORTID}' => $res ['all'] ['IDENTIFICATION_SHORTID']);

        return strtr(constant('PREPAYMENT_TEXT'), $repl);
    }
}
