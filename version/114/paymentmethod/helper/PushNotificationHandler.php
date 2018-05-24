<?php
/*
 * Class to handle push notifications
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/vendor/autoload.php';
/*require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/'
    . $oPlugin->nVersion . '/paymentmethod/heidelpay_standard.class.php';*/

use Heidelpay\PhpPaymentApi\Push;

/**
 * Class PushNotificationHandler
 */
class PushNotificationHandler
{
    /**
     * @var \Heidelpay\PhpPaymentApi\Response|null
     */
    private $response;
    /**
     * @var heidelpay_standard
     */
    private $paymentModule;
    /**
     * @var Plugin
     */
    private $oPlugin;

    /**
     * PushNotificationHandler constructor.
     * @param $xmlResponse
     */
    public function __construct($xmlResponse)
    {
        global $oPlugin;
        // Check POST-data and assign response object
        $this->init($xmlResponse);

        $this->oPlugin = $this->getPluginFromResponse();
        $oPlugin = $this->oPlugin;

        Jtllog::writeLog('version '.$this->oPlugin->nVersion);
        $this->checkSecurityHash();

        $moduleID = $this->getModuleIdFromResponse($this->response);

        if (!empty($moduleID)) {
            $shopPaymethod = Shop::DB()->select('tpluginzahlungsartklasse', 'cModulId', $moduleID);
            Jtllog::writeLog(print_r($shopPaymethod,1));

            try {
                require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/114/paymentmethod/'
                    .$shopPaymethod->cClassPfad;
                $classname = $shopPaymethod->cClassName;
                $this->paymentModule = new $classname($moduleID);
                Jtllog::writeLog('PaymentModule created');
            } catch (\Exception $exception) {
                Jtllog::writeLog('heidelpay push-log: Push notification could not be processed - Paymethod not found: '
                    .$moduleID);
            }
        }
    }

    private function loadPaymentModule()
    {

    }

    /**
     * @param $xmlResponse
     * @return void
     */
    private function init($xmlResponse)
    {
        if (empty($xmlResponse)) {
            http_response_code(200);
            exit();
        }
        // Try to create a response-object from push
        try {
            $pushResponse = new Push($xmlResponse);
            $this->response = $pushResponse->getResponse();
        } catch (\Exception $e) {
            http_response_code(200);
            exit();
        }
    }

    /**
     * @return Plugin
     */
    private function getPluginFromResponse()
    {
        $moduleID = $this->getModuleIdFromResponse($this->response);
        $kPlugin = gibkPluginAuscModulId($moduleID);
        return new Plugin($kPlugin);
    }

    /**
     * @param \Heidelpay\PhpPaymentApi\Response
     * @return mixed
     */
    private function getModuleIdFromResponse($response)
    {
        if (!empty($response)) {
            return $response->getCriterion()->get('PAYMETHOD');
        }
        http_response_code(200);
    }

    /**
     * @return bool
     */
    private function checkSecurityHash()
    {
        $secretPass = $this->oPlugin->oPluginEinstellungAssoc_arr ['secret'];
        $identificationTransactionId = $this->response->getIdentification()->getTransactionId();

        try {
            $this->response->verifySecurityHash($secretPass, $identificationTransactionId);
        } catch (\Exception $e) {
            $callers = debug_backtrace();

            Jtllog::writeLog("Heidelpay - " . $callers [0] ['function'] . ": Invalid response hash from " .
                $_SERVER ['REMOTE_ADDR'] . ", suspecting manipulation", JTLLOG_LEVEL_NOTICE, false, 'Notify');
            exit();
        }

        return true;
    }

    /**
     * Handle incoming push notifications.
     * Only incoming payments are handled that are successful and not pending.
     */
    public function handlePush()
    {
        $paymentCode = $this->response->getPayment()->getCode();
        $transactionType = explode('.', $paymentCode)[1];

        $statusChange = $this->checkStatusChange($transactionType);
        $order = $this->loadOrder($this->response);

        $orderUpdate = new stdClass();
        $orderUpdate->cStatus = $statusChange;

        if ($this->response->isSuccess() && !$this->response->isPending() && $order != null) {
            $incomingPayment = new stdClass();
            $incomingPayment->fBetrag = $this->response->getPresentation()->getAmount();
            $incomingPayment->cISO = $this->response->getPresentation()->getCurrency();
            $incomingPayment->cHinweis = $this->response->getIdentification()->getUniqueId();

            if ($statusChange == BESTELLUNG_STATUS_BEZAHLT) {
                $this->addIncomingPayment($order, $incomingPayment);
            }
            
            /*if ($statusChange == BESTELLUNG_STATUS_STORNO) {
                if($order->fGesamtsumme == $this->response->getPresentation()->getAmount()) {
                    //$this->setOrderStatus($order, $orderUpdate);
                    $this->paymentModule->cancelOrder($order->kBestellung);
                    Jtllog::writeLog('storno case:'.$order->fGesamtsumme.
                        ' response amount: '.$this->response->getPresentation()->getAmount());
                }

                if($order->fGesamtsumme > $this->response->getPresentation()->getAmount()) {
                }
            }*/
        }
    }

    private function setOrderStatus ($order, $orderUpdate)
    {
        Shop::DB()->update('tbestellung', 'cBestellNr', $order->cBestellNr, $orderUpdate);
    }

    /**
     * @param $transactionType
     * @return int|null
     */
    private function checkStatusChange($transactionType)
    {
        switch ($transactionType) {
            case 'RC':
            case 'DB':
            case 'CP':
            case 'RB':
                return BESTELLUNG_STATUS_BEZAHLT;
                break;
            case 'CB':
                return BESTELLUNG_STATUS_STORNO;
                break;
            default:
                return null;
        }
    }

    /**
     * Load order from the database depending on transaction ID.
     * If payment was done before order was created the transaction uses a temporary order number.
     * @param $response
     * @return Bestellung|null
     */
    private function loadOrder(\Heidelpay\PhpPaymentApi\Response $response)
    {
        $bestellNr = $response->getIdentification()
            ->getTransactionId();
        $bestellRef = Shop::DB()->select('xplugin_heidelpay_standard_order_reference', 'cTempBestellNr', $bestellNr);

        // if a reference to an order exists use use that order number instead.
        if($bestellRef) {
            $bestellNr = $bestellRef->cBestellNr;
        }

        $bestellungDB = Shop::DB()->select('tbestellung', 'cBestellNr', $bestellNr);

        if (!empty($bestellungDB)) {
            $order = new Bestellung($bestellungDB->kBestellung);
        } else {
            Jtllog::writeLog('heidelpay push-gw: No Order Found matching the transaction with short ID: '
                . $response->getIdentification()->getShortId(), JTLLOG_LEVEL_ERROR);
            return null;
        }

        return $order;
    }

    /**
     * @param $order
     * @param $incomingPayment
     */
    private function addIncomingPayment($order, $incomingPayment)
    {
        if ($this->paymentExists($incomingPayment)) {
            return;
        }
        $this->paymentModule->addIncomingPayment($order, $incomingPayment);
    }

    /**
     * @param $incomingPayment
     * @return bool
     */
    private function paymentExists($incomingPayment)
    {
        $dbPayment = Shop::DB()->select('tzahlungseingang', 'cHinweis', $incomingPayment->cHinweis);

        return !empty($dbPayment);

        /*if (empty($dbPayment)) {
            return false;
        }
        return true;*/
    }

    /**
     *
     */
    public function saveResponse()
    {
        $referenceId = $this->referenceExists() ? $this->response->getIdentification()->getReferenceId() : NULL;

        $dbResponse = new stdClass();
        $dbResponse->transaction_id = $this->response->getIdentification()->getTransactionId();
        $dbResponse->unique_id = $this->response->getIdentification()->getUniqueId();
        $dbResponse->reference_id = $referenceId;
        $dbResponse->timestamp = $this->response->getProcessing()->timestamp;

        $pluginTableName = 'xplugin_heidelpay_standard_push_notification';
        Shop::DB()->insert($pluginTableName, $dbResponse);
    }

    /**
     * @return bool
     */
    public function referenceExists()
    {
        $reference = $this->response->getIdentification()->getReferenceId();
        if (empty($reference)) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isTimeStampNew()
    {
        $previousPush = Shop::DB()->select('xplugin_heidelpay_standard_push_notification',
            'unique_id',
            $this->response->getIdentification()->getUniqueId());

        if ($previousPush !== null) {
            return $previousPush->timestamp <= $this->response->getProcessing()->timestamp;
        }
        return true;
    }
}