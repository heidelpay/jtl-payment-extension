<?php
/*
 * Basic abstract class for payment methods.
 *
 * DESC
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author Ronja Wann, Florian Evertz, David Owusu
 * @category JTL
 */
include_once PFAD_ROOT . PFAD_INCLUDES_MODULES . 'ServerPaymentMethod.class.php';
require_once PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cVerzeichnis .DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
require_once PFAD_ROOT . PFAD_CLASSES . "class.JTL-Shop.Jtllog.php";
require_once __DIR__ . DIRECTORY_SEPARATOR .'helper'.DIRECTORY_SEPARATOR .'HeidelpayBasketHelper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR .'helper'.DIRECTORY_SEPARATOR .'HeidelpayTemplateHelper.php';

/*
 * heidelpay standard class
 */

class heidelpay_standard extends ServerPaymentMethod
{
    /**
     * @var \Heidelpay\PhpPaymentApi\PaymentMethods\BasicPaymentMethodTrait
     */
    public $paymentObject;
    public $pluginName = "heidelpay_standard";
    /**
     * @var Plugin
     */
    public $oPlugin;

    /**
     * Sets Short-ID in database as comment for the order
     *
     * @param $shortId
     * @param $orderId
     * @return bool
     */
    public function setShortId($shortId, $orderId)
    {
        $shortId = preg_match('/[0-9]{4}\.[0-9]{4}\.[0-9]{4}/', $shortId) ? $shortId : false;
        if (!is_numeric($orderId) || $shortId == false) {
            return false;
        }

        $updateOrder = new stdClass();
        $updateOrder->cKommentar = $shortId;
        Shop::DB()->update('tbestellung', 'cBestellNr', $orderId, $updateOrder);
    }

    /**
     * generates hash for criterion secret with secretPhrase and orderID
     *
     * @param $secret String secret phrase from backend
     * @param $orderId
     * @return string hashed secret string
     */
    public function getHash($secret, $orderId)
    {
        return hash('sha256', $secret . $orderId);
    }

    /**
     * Initialize the payment process by set the payment method and the plugin.
     */
    protected function initPaymentProcess()
    {
        $this->setPaymentObject();
        $this->oPlugin = $this->getPlugin($this->moduleID);
    }

    /**
     * Prepares process for payment
     *
     * @param Bestellung $order
     */
    public function preparePaymentProcess($order)
    {
        $this->init(0);
        $this->prepareRequest($order, $this->moduleID);

        try {
            $this->sendPaymentRequest();
        } catch (\Exception $exception) {
            $this->redirect($this->getErrorReturnURL($order));
        }

        if ($this->paymentObject->getResponse()->isError()) {
            $error = $this->paymentObject->getResponse()->getError();
            $errorCode = !empty($error['code']) ? $error['code'] : '000.000.000';

            $logData = array(
                'module' => 'heidelpay Standard',
                'response' => $this->paymentObject->getResponse()
            );

            Jtllog::writeLog('Transaction error: ' . print_r($logData, true), JTLLOG_LEVEL_NOTICE);
            $errorPage = $this->getErrorReturnURL($order);
            $parameterConnector = preg_match('/.php$/', $errorPage) ? '?' : '&';

            $this->redirect($this->getErrorReturnURL($order) . $parameterConnector . 'hperror=' . $errorCode);
        } else {
            $this->setPaymentTemplate();
        }

    }

    /**
     * Check whether order has same address for billing and shipping and
     * whether it ist b2c.
     *
     * @param $order
     */
    protected function b2cSecuredCheck($order)
    {
        if ($this->isEqualAddress($order) == false) {
            $this->redirect(Shop::getURL() . '/warenkorb.php?hperroradd=1');
        }

        if ($_SESSION['Kunde']->cFirma != null) {
            $this->redirect(Shop::getURL() . '/warenkorb.php?hperrorcom=1');
        }
    }

    /**
     * Prepare transaction request.
     * The preparations will apply to $this->paymentObject
     * @param Bestellung $order
     * @param string $currentPaymentMethod
     */
    protected function prepareRequest(Bestellung $order, $currentPaymentMethod)
    {
        global $oPlugin;
        $hash = $this->generateHash($order);
        $notifyURL = $this->getNotificationURL($hash);

        $this->paymentObject->getRequest()->authentification(
            trim($oPlugin->oPluginEinstellungAssoc_arr ['sender']),
            trim($oPlugin->oPluginEinstellungAssoc_arr ['user']),
            trim($oPlugin->oPluginEinstellungAssoc_arr ['pass']),
            trim($oPlugin->oPluginEinstellungAssoc_arr [$currentPaymentMethod . '_channel']),
            $this->isSandboxMode($oPlugin, $currentPaymentMethod)
        );

        $this->paymentObject->getRequest()->getContact()->set('ip', $this->getIp());
        $this->paymentObject->getRequest()->customerAddress(...$this->getCustomerData());
        $this->paymentObject->getRequest()->basketData(...$this->getBasketData($order, $oPlugin));
        $this->paymentObject->getRequest()->async($this->getLanguageCode(), $notifyURL);
        // Set Criterions
        $this->paymentObject->getRequest()->getCriterion()->set('PAYMETHOD', $currentPaymentMethod);
        $this->paymentObject->getRequest()->getCriterion()->set('PUSH_URL', Shop::getURL().'/'.urlencode('push-gw'));
        $this->paymentObject->getRequest()->getCriterion()->set('SHOP.TYPE', 'JTL '.Shop::getVersion());
        $this->paymentObject->getRequest()->getCriterion()->set('SHOPMODULE.VERSION', 'heidelpay gateway '.$oPlugin->nVersion);
    }

    /**
     * Send the payment request using authorize as default.
     * Override this method in the child class if another transaction mode should be used.
     *
     * @throws \Heidelpay\PhpPaymentApi\Exceptions\UndefinedTransactionModeException
     */
    protected function sendPaymentRequest()
    {
        $this->paymentObject->authorize();
    }

    /**
     * Build and send a basket to the hPP. If successful the basketId will be added to the payment transaction.
     * @param string $currentPaymentMethod
     * @param Bestellung $order
     */
    protected function addBasketId($currentPaymentMethod, Bestellung $order) {
        $oPlugin = $this->getPlugin($currentPaymentMethod);
        $response = HeidelpayBasketHelper::sendBasketFromOrder(
            $order,
            $oPlugin->oPluginEinstellungAssoc_arr,
            $this->isSandboxMode($oPlugin, $currentPaymentMethod)
        );

        if($response->isSuccess()) {
            $this->paymentObject->getRequest()->getBasket()->setId($response->getBasketId());
        } else {
            Jtllog::writeLog('No basket could be added to the order. Order number: '
                .$order->cBestellNr, JTLLOG_LEVEL_NOTICE);
        }
    }

    /**
     * returns plugin depending on current payment method
     *
     * @param $moduleID
     * @return bool|Plugin
     */
    public function getPlugin($moduleID)
    {
        $kPlugin = gibkPluginAuscModulId($moduleID);
        if ($kPlugin > 0) {
            $oPlugin = new Plugin($kPlugin);
        } else {
            return false;
        }

        return $oPlugin;
    }

    /**
     * Initial function
     *
     * @param int $nAgainCheckout
     */
    public function init($nAgainCheckout = 0)
    {
        parent::init($nAgainCheckout);

        $this->name = 'heidelpay';
        $this->caption = 'heidelpay';

        $this->info = Shop::DB()->select('tzahlungsart', 'cModulId', $this->moduleID);
        $this->initPaymentProcess();
    }

    /**
     * Gets prefix of current payment method
     *
     * @param $oPlugin
     * @param $moduleId
     * @return string current payment method prefix
     */
    public function getCurrentPaymentMethodPrefix($oPlugin, $moduleId)
    {
        $payCode = strtolower($oPlugin->oPluginEinstellungAssoc_arr [$moduleId . '_paycode']);
        return strtoupper('HP' . $payCode);
    }

    /**
     * Sets payment object for the chosen payment method
     */
    public function setPaymentObject()
    {
        return false;
    }

    /**
     * Checks if Sandbox-Mode active or not
     *
     * @param $oPlugin
     * @return bool true = sandbox mode active, false = live mode active (productive system)
     */
    protected function isSandboxMode($oPlugin, $currentPaymentMethod)
    {
        if ($oPlugin->oPluginEinstellungAssoc_arr [$currentPaymentMethod . '_transmode'] == 'LIVE') {
            return false;
        }
        return true;
    }

    /**
     * gets IP from client
     *
     * @return ip address
     */
    public function getIp()
    {
        $ip = '127.0.0.1';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            if (filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP) !== FALSE) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if (filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP) !== FALSE) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) !== FALSE) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }
        return $ip;
    }

    /**
     * Gets customer data from current session
     * sets customer address on shipping address in case of PayPal for PayPal buyer protection
     *
     * @return array with user data (name, address and mail)
     */
    public function getCustomerData()
    {
        $user = $_SESSION ['Kunde'];
        $userStreet = $user->cStrasse . ' ' . $user->cHausnummer;
        $userData = array(empty($user->cVorname) ? null : $user->cVorname,
            empty($user->cNachname) ? null : $user->cNachname,
            empty($user->cFirma) ? null : $user->cFirma,
            empty($user->kKunde) ? null : $user->kKunde,
            empty($userStreet) ? null : $userStreet,
            empty($user->cBundesland) ? null : $user->cBundesland,
            empty($user->cPLZ) ? null : $user->cPLZ,
            empty($user->cOrt) ? null : $user->cOrt,
            empty($user->cLand) ? null : $user->cLand,
            empty($user->cMail) ? null : $user->cMail);

        return $this->encodeData($userData);
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
        $_SESSION['hp_temp_orderId'] = $orderId;
        Jtllog::writeLog('orderID: '.$orderId, JTLLOG_LEVEL_DEBUG);

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
     * Gets language code depending on language in session
     *
     * @return string language code
     */
    public function getLanguageCode()
    {
        $language = strtoupper(StringHandler::convertISO2ISO639($_SESSION['cISOSprache']));
        return $language;
    }

    /**
     * billing and shipping address has to be equal
     *
     * @param $order
     * @return bool
     */
    public function isEqualAddress($order)
    {
        $keyList = array(
            'cVorname',
            'cNachname',
            'cAnrede',
            'cFirma',
            'cStrasse',
            'cHausnummer',
            'cOrt',
            'cPLZ',
            'cLand'
        );
        foreach ($keyList as $key) {
            // key exists only in billing address
            if (array_key_exists($key, (array)$order->oRechnungsadresse) and
                !array_key_exists($key, (array)$order->Lieferadresse)) {
                return false;
            }
            // key exists only in delivery address
            if (array_key_exists($key, (array)$order->Lieferadresse) and
                !array_key_exists($key, (array)$order->oRechnungsadresse)) {
                return false;
            }
            // merge keys
            if (array_key_exists($key, (array)$order->Lieferadresse) and
                array_key_exists($key, (array)$order->oRechnungsadresse)) {
                // return false on unmatched
                if ($order->Lieferadresse->$key != $order->oRechnungsadresse->$key) {
                    return false;
                }
            }
        }
        // if everything is equal return true
        return true;
    }

    /**
     * Redirects customer
     */
    public function redirect($url)
    {
        header('Location: ' . $url);
    }

    /**
     * Get booking mode
     *
     * @param $oPlugin
     * @param $currentPaymentMethod
     * @return mixed returns booking mode
     */
    public function getBookingMode($oPlugin, $currentPaymentMethod)
    {
        $bookingMode = $oPlugin->oPluginEinstellungAssoc_arr [$currentPaymentMethod . '_bookingmode'];
        return $bookingMode;
    }

    /**
     * Gets payment frame origin
     *
     * @return string url with the origin of the payment frame
     */
    public function getPaymentFrameOrigin()
    {
        $parse_url = parse_url(Shop::getURL());
        $paymentFrameOrigin = $parse_url['scheme'] . '://' . $parse_url['host'];
        return $paymentFrameOrigin;
    }

    /**
     * Sets payment template depending on chosen payment method
     *
     * @param $paymentMethodPrefix
     *
     */
    public function setPaymentTemplate()
    {
        global $smarty;
        $templateHelper = new HeidelpayTemplateHelper($this);

        $smarty->assign('pay_button_label', $this->getPayButtonLabel());
        $smarty->assign('paytext', utf8_decode($this->getPayText()));

        $formFields = $this->getFormFields();
        if($formFields) {
            $templateHelper->addFieldsets($smarty, $formFields);
        } else {
            $this->redirect($this->paymentObject->getResponse()->getPaymentFormUrl());
        }
    }

    /**
     * @return array|null
     */
    public function getFormFields()
    {
        $paymentMethodPrefix = $this->getCurrentPaymentMethodPrefix($this->oPlugin, $this->moduleID);
        switch ($paymentMethodPrefix) {
            /** @noinspection Fallthrough */
            case 'HPCC':
            case 'HPDC':
            case 'HPDD':
                return ['holder'];
                break;
            case 'HPDDPG':
            case 'HPIVPG':
                return [
                    'holder',
                    'birthdate',
                    'salutation',
                    'is_PG',
                ];
                break;
            /** @noinspection Fallthrough */
            case 'HPIDL':
            case 'HPEPS':
                return [
                    'account',
                    'bank'
                ];
                break;
            case 'HPSA':
                return [
                    'birthdate',
                    'privacy',
                    'salutation',
                    'holder',
                ];
                break;
            default:
                return null;
        }
    }

    /**
     * Gets label on pay button depending on selected language
     *
     * @return string with text for pay button
     */
    public function getPayButtonLabel()
    {
        return $this->oPlugin->oPluginSprachvariableAssoc_arr['hp_paybutton'];
    }

    /**
     * Gets payment text depending on selected language
     *
     * @return string with payment text
     */
    public function getPayText()
    {
        return utf8_encode($this->oPlugin->oPluginSprachvariableAssoc_arr['hp_paytext']);
    }

    /**
     * Gets label for holder depending on selected language
     *
     * @return string with text for holder label
     */
    public function getHolderLabel()
    {
        return utf8_encode($this->oPlugin->oPluginSprachvariableAssoc_arr['hp_holderlabel']);
    }

    /**
     * Gets label for birthdate depending on selected language
     *
     * @return string with text for birthdate label
     */
    public function getBirthdateLabel()
    {
        return utf8_encode($this->oPlugin->oPluginSprachvariableAssoc_arr['hp_birthdatelabel']);
    }

    public function getLocalizedString($localizeKey)
    {
        $localizedText = $this->oPlugin->oPluginSprachvariableAssoc_arr[$localizeKey];

        if(!empty($localizedText)) {
            return utf8_encode($localizedText);
        } else {
            $callers = debug_backtrace();
            Jtllog::writeLog(
                $callers[1] . 'heidelpay_standard: No translation could be found for: ' . $localizeKey . '.',
                JTLLOG_LEVEL_NOTICE
            );
            return '';
        }
    }

    /**
     * Creates salutation array for template depending on session language
     *
     * @return array with salutation options
     */
    public function getSalutationArray()
    {
        $salutationArray = [
            'MR' => utf8_encode($this->oPlugin->oPluginSprachvariableAssoc_arr['hp_salutation_male']),
            'MRS' => utf8_encode($this->oPlugin->oPluginSprachvariableAssoc_arr['hp_salutation_female'])
        ];
        return $salutationArray;
    }

    /**
     * Gets salutation from session for payment
     *
     * @return string 'MR' or 'MRS' depending on the salutation in session
     */
    public function getSalutation()
    {
        $salutation = $this->oPlugin->oPluginSprachvariableAssoc_arr['hp_salutation_female'];
        if ($_SESSION['Kunde']->cAnrede == 'm') {
            $salutation = $this->oPlugin->oPluginSprachvariableAssoc_arr['hp_salutation_male'];
        }
        return $salutation;
    }

    /**
     * Gets private policy depending on language
     *
     * @param $oPlugin
     * @return mixed text with private policy
     */
    public function getPrivatePolicyLabel($oPlugin)
    {
        return $this->oPlugin->oPluginSprachvariableAssoc_arr['hp_holderlabel'];
    }

    /**
     * Handles notification and redirects customer
     *
     * @param $order
     * @param $paymentHash
     * @param $args
     */
    public function handleNotification($order, $paymentHash, $args)
    {
        $this->init();

        $heidelpayResponse = new  Heidelpay\PhpPaymentApi\Response($args);
        $this->checkHash($args, $heidelpayResponse);

        /** Ensure that the mail language is not overwritten by the session language */
        $this->unsetSessionLanguage();

        if ($heidelpayResponse->isSuccess()) {
            /* save order and transaction result to your database */
            if ($this->verifyNotification($order, $args)) {
                $payCode = explode('.', $args ['PAYMENT_CODE']);

                // Send mail with payment information i.e. direct debit and pre payment
                if(!isset($args ['TRANSACTION_SOURCE'])) {
                    $this->sendPaymentMail($order, $args);
                    $this->setPayInfo($args, $order);
                }

                // Nur wenn nicht Vorkasse od. Rechnung
                if (strtoupper($payCode [0]) != 'PP' AND strtoupper($payCode [0]) != 'IV') {
                    $this->setOrderStatusToPaid($order);
                    $this->sendConfirmationMail($order);
                }

                if (strtoupper($payCode [1]) != 'PA' AND strtoupper($payCode [1]) != 'RG') {
                    $incomingPayment = new stdClass();
                    $incomingPayment->fBetrag = number_format($order->fGesamtsummeKundenwaehrung, 2, '.', '');
                    $incomingPayment->cISO = $order->Waehrung->cISO;
                    $incomingPayment->cHinweis = (array_key_exists('IDENTIFICATION_UNIQUEID', $args)
                    ? $args['IDENTIFICATION_UNIQUEID'] : '');
                    $this->addIncomingPayment($order, $incomingPayment);
                }
                $this->updateNotificationID($order->kBestellung, $args['IDENTIFICATION_UNIQUEID']);
            }
            /* redirect customer to success page */
            echo $this->getReturnURL($order);

            /*save order */
        } elseif ($heidelpayResponse->isError()) {
            $error = $heidelpayResponse->getError();
            echo $this->getErrorReturnURL($order) . '&hperror=' . $error['code'] .
                $this->disableInvoiceSecured($args);
        } elseif ($heidelpayResponse->isPending()) {
            echo $this->getReturnURL($order);
        }
    }

    /**
     * Send a mail to the customer with paymentinformation if necessary.
     * By default no mail is send.
     * @param Bestellung $order
     * @param $args
     */
    public function sendPaymentMail(Bestellung $order, $args)
    {
        $templateId = $this->getInfoTemplateId();
        $mailingObject = $this->setInfoContent($args);

        if(empty($templateId) || empty($mailingObject)) {
            return;
        }

        //Prepare customer object for mailObject
        $tkunde = new stdClass();
        $tkunde->cMail = $order->oRechnungsadresse->cMail;
        $tkunde->kSprache = $order->kSprache;

        $mailingObject->tkunde = $tkunde;

        $template = 'kPlugin_' . $this->oPlugin->kPlugin . '_' . $templateId . '';
        sendeMail( $template , $mailingObject);
    }

    public function setOrderStatusToPaid($order)
    {
        try {
            parent::setOrderStatusToPaid($order);
        } catch (Exception $e) {
            $e = 'Update order status failed on order: ' . $order . ' in file: ' .
                $e->getFile() . ' on line: ' . $e->getLine() . ' with message: ' . $e->getMessage();
            $logData = array(
                'module' => 'heidelpay Standard',
                'order' => $order,
                'error_msg' => $e
            );
            Jtllog::writeLog(print_r($logData,true), JTLLOG_LEVEL_ERROR, false);
        }
    }

    /**
     * Send confirmation mail to customer.
     * @param Bestellung $order
     * @return $this|void
     */
    public function sendConfirmationMail($order)
    {
        try {
            parent::sendConfirmationMail($order);
        } catch (Exception $e) {
            $e = 'Update order status failed on order: ' . $order . ' in file: ' .
                $e->getFile() . ' on line: ' . $e->getLine() . ' with message: ' . $e->getMessage();
            $logData = array(
                'module' => 'heidelpay Standard',
                'order' => $order,
                'error_msg' => $e
            );
            Jtllog::writeLog(print_r($logData,true), JTLLOG_LEVEL_ERROR, false);
        }
    }

    /**
     * Verifies notification
     *
     * @return boolean
     * @param Bestellung $order
     * @param array $post
     */
    public function verifyNotification($order, $post)
    {
        if ($post['CLEARING_AMOUNT'] != number_format($order->fGesamtsummeKundenwaehrung, 2, '.', '')) {
            return false;
        }

        if ($post['CLEARING_CURRENCY'] != $order->Waehrung->cISO) {
            return false;
        }
        return true;
    }

    /**
     * Verify the SecurityHash.
     * If the verification does not match this can mean some kind of manipulation or
     * miss configuration. So you can log $e->getMessage() for debugging.
     *
     * @param array $args Data of the transaction resault
     * @param \Heidelpay\PhpPaymentApi\Response $heidelpayResponse
     */
    public function checkHash($args, $heidelpayResponse)
    {
        if (array_key_exists('CRITERION_PAYMETHOD', $args)) {
            //global $oPlugin;
            $oPlugin = $this->getPlugin($args['CRITERION_PAYMETHOD']);
            $secretPass = $oPlugin->oPluginEinstellungAssoc_arr ['secret'];
            $identificationTransactionId = $heidelpayResponse->getIdentification()->getTransactionId();
            try {
                $heidelpayResponse->verifySecurityHash($secretPass, $identificationTransactionId);
            } catch (\Exception $e) {
                $callers = debug_backtrace();
                Jtllog::writeLog("Heidelpay - " . $callers [0] ['function'] . ": Invalid response hash from " .
                    $_SERVER ['REMOTE_ADDR'] . ", suspecting manipulation", JTLLOG_LEVEL_NOTICE, false, 'Notify');
                exit();
            }
        } else {
            $this->redirect(Shop::getURL() . '/bestellvorgang.php');
        }
    }

    /**
     * Sets payment information as comment in database. Default is to write no payInfo
     *
     * @param $args response form payment
     * @param $order
     */
    protected function setPayInfo($args, $order)
    {
        $templateId = $this->getInfoTemplateId();
        $infoContent = $this->setInfoContent($args);

        if(empty($templateId) || empty($infoContent)) {
            return;
        }

        $infoTemplate = $this->loadInfoTemplate($templateId, $order);
        $infoText = $this->prepareInfoText($infoContent, $infoTemplate);

        if (empty($infoTemplate) || empty($infoText)) {
            return;
        }

        $updateOrder = new stdClass();
        $updateOrder->cKommentar = $infoText;
        $updateOrder->cPUIZahlungsdaten = $infoText;

        Shop::DB()->update('tbestellung', 'cBestellNr', htmlspecialchars($order->cBestellNr), $updateOrder);
        Jtllog::writeLog('updated payinfo: ' . print_r(shop::DB()->select(
                'tbestellung',
                'cBestellNr', htmlspecialchars($order->cBestellNr)),
                true
            ),
            JTLLOG_LEVEL_NOTICE
        );
    }

    /**
     * Error return url clone from PaymentMethod.class because of bestellabschluss case
     * @param Bestellung $order
     * @return string
     */
    public function getErrorReturnURL($order)
    {
        if (!isset($_SESSION['Zahlungsart']->nWaehrendBestellung) ||
            $_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
            return $order->BestellstatusURL;
        }

        return Shop::getURL() . '/bestellvorgang.php';
    }

    public function disableInvoiceSecured($response)
    {
        $payCode = explode('.', $response ['PAYMENT_CODE']);
        if($payCode[0] === 'IV') {
            if (array_key_exists('CRITERION_INSURANCE-RESERVATION', $response) &&
                $response['CRITERION_INSURANCE-RESERVATION'] === 'DENIED') {
                return '&disableInvoice=true';
            }
        }

        return '';
    }

    public function finalizeOrder($order, $hash, $args)
    {
        global $cEditZahlungHinweis;

        if ($args['PROCESSING_RESULT'] == "ACK") {
            return true;
        } else {
            $cEditZahlungHinweis = rawurlencode($args['PROCESSING_RETURN']) .
                '&hperror=' . $args['PROCESSING_RETURN_CODE'];

            if (isset($args['CRITERION_INSURANCE-RESERVATION']) &&
                $args['CRITERION_INSURANCE-RESERVATION'] === 'DENIED') {
                $cEditZahlungHinweis .= $this->disableInvoiceSecured($args);
            }
            return false;
        }
    }

    /**
     * @param $templateId
     * @param $tkunde
     * @return null|object
     */
    public function loadInfoTemplate($templateId, $order)
    {
        if(empty($templateId)) {
            return null;
        }
        $Emailvorlage = Shop::DB()->select(
            'tpluginemailvorlage',
            'kPlugin', $this->oPlugin->kPlugin,
            'cModulId', $templateId
        );

        if (!empty($Emailvorlage->kEmailvorlage)) {
            return Shop::DB()->select(
                'tpluginemailvorlagesprache',
                'kEmailvorlage', $Emailvorlage->kEmailvorlage,
                'kSprache', $order->kSprache
            );
        }

        Jtllog::writeLog('heidelpay - infoTemplate: could not be loaded', JTLLOG_LEVEL_NOTICE);
        return null;
    }

    /**
     * @param $infoContent
     * @param $templateText
     * @return string
     */
    public function prepareInfoText(stdClass $infoContent, $templateText)
    {
        $templateArray = [];

        // Make the array keys fit those of the email template.
        foreach (get_object_vars($infoContent) as $key => $value) {
            $key = '{$oPluginMail->' . $key . '}';
            $templateArray[$key] = $value;
        }
        if(!empty($templateText->cContentText)) {
            return strtr($templateText->cContentText, $templateArray);
        }
        Jtllog::writeLog('heidelpay - infoText: No conten text was set for', JTLLOG_LEVEL_NOTICE);
        return null;
    }

    /**
     * Provides the Id of the used Template. Can be overwritten by childclass
     * @return string|null
     */
    public function getInfoTemplateId()
    {
        return null;
    }

    /**
     * Provide the mailingObject which will be used for payment info mail.
     *
     * @param $args
     * @return @return stdClass|null
     */
    public function setInfoContent($args)
    {
        return null;
    }

    /** If value is set the function sendConfirmationMail will use the session language instead the order
     * language. Therefore we unset the session language value.
     */
    protected function unsetSessionLanguage()
    {
        if (isset($_SESSION['currentLanguage'])) {
            unset($_SESSION['currentLanguage']);
        }
    }


}
