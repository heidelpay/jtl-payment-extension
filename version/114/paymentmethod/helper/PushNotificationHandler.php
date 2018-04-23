<?php
/**
 * Created by PhpStorm.
 * User: David.Owusu
 * Date: 13.04.2018
 * Time: 14:04
 */
require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/vendor/autoload.php';

use Heidelpay\PhpPaymentApi\Push;

class PushNotificationHandler
{
    public $isHashValid;
    /**
     * @var \Heidelpay\PhpPaymentApi\Response|null
     */
    private $response;
    private $waitTime;
    private $paymentModule;
    private $oPlugin;

    public function __construct($xmlResponse)
    {
        global $oPlugin;

        // Check for POST-data
        if (empty($xmlResponse)) {
            http_response_code(400);
            exit();
        }
        // Try to create a response-object from push
        try {
            $pushResponse = new Push($xmlResponse);
            $this->response = $pushResponse->getResponse();
        } catch (\Exception $e) {
            http_response_code(400);
        }

        $this->oPlugin = $this->getPluginFromResponse();
        $oPlugin = $this->oPlugin;
        $this->checkSecurityHash();

        $moduleID = $this->getModuleIdFromResponse($this->response);

        if (!empty($moduleID)) {
            require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/version/'
                . $oPlugin->nVersion . '/paymentmethod/heidelpay_standard.class.php';

            $this->paymentModule = new heidelpay_standard($moduleID);
        }

        $logData = [
            'moudleId' => $moduleID,
            'response' => $this->response
        ];

        Jtllog::writeLog('PNH init: ' . print_r($logData, 1), 4);
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

    private function getModuleIdFromResponse($response)
    {
        /*try {
            return $response->getCriterion()->get('PAYMETHOD');
        } catch(\Exception $e) {
            http_response_code(400);
        }*/

        if (!empty($response)) {
            return $response->getCriterion()->get('PAYMETHOD');
        } else {
            http_response_code(400);
        }
    }

    public function checkSecurityHash()
    {
        //global $oPlugin;
        $secretPass = $this->oPlugin->oPluginEinstellungAssoc_arr ['secret'];
        $identificationTransactionId = $this->response->getIdentification()->getTransactionId();

        /*$logData = [
            'secret' => $this->oPlugin,
            'identificationTransactionId' => $identificationTransactionId
        ];

        Jtllog::writeLog(print_r($logData,1), 4);*/

        try {
            $this->response->verifySecurityHash($secretPass, $identificationTransactionId);
            Jtllog::writeLog('security Hash is valid', 4);
        } catch (\Exception $e) {
            $callers = debug_backtrace();
            /*Jtllog::writeLog("Heidelpay - " . $callers [0] ['function'] . ": Invalid response hash from " .
                $_SERVER ['REMOTE_ADDR'] . ", suspecting manipulation", JTLLOG_LEVEL_NOTICE, false, 'Notify'); */

            Jtllog::writeLog("Heidelpay - " . $callers [0] ['function'] . ":".$e->getMessage()." Remote ip-address: " .
                $_SERVER ['REMOTE_ADDR'], JTLLOG_LEVEL_NOTICE, false, 'Notify');
            exit();
        }


        return true;
    }

    public function handlePush()
    {
        $paymentCode = $this->response->getPayment()->getCode();
        $transactionType = explode('.', $paymentCode)[1];

        $statusChange = $this->checkStatusChange($transactionType);
        $order = $this->loadOrder($this->response);

        $orderUpdate = new stdClass();
        $orderUpdate->cStatus = $statusChange;
        $orderUpdate->cBestellNr = $order->cBestellNr;
        $orderUpdate->kBestellung = $order->kBestellung;

        $logData = array();

        if ($this->response->isSuccess() && !$this->response->isPending()) {

            if ($statusChange = BESTELLUNG_STATUS_BEZAHLT) {
                $incomingPayment = new stdClass();
                $incomingPayment->fBetrag = $this->response->getPresentation()->getAmount();
                $incomingPayment->cISO = $this->response->getPresentation()->getCurrency();
                $incomingPayment->cHinweis = $this->response->getIdentification()->getUniqueId();

                $this->addIncomingPayment($order, $incomingPayment);
                $this->paymentModule->sendConfirmationMail($order);
            }

            /*if ($this->response->getPresentation()->getAmount() >= $order->fGesamtsumme) {
                if ($statusChange != false AND $order->cStatus != $statusChange) {
                    $this->paymentModule->setOrderStatusToPaid($order);
                }
            } else {
                Jtllog::writeLog('heidelpay push-gw: Amount was too low', 3);
            }*/
        }

        if ($this->response->isPending()) {
            $logData['Response Status'] = 'isPending';
        }

        if ($this->response->isSuccess() && !$this->response->isPending()) {
            $logData['Response Status'] = 'Tüdelü not pending and success!!';
        }

        $logData['||=---- TransactionId |--->'] = $order->cBestellNr;
        $logData['||=---- Bestellung |--->'] = $order;

        Jtllog::writeLog('heidelpay push-gw: Push log - ' . print_r($logData, 1), 4);
    }

    public function checkStatusChange($transactionType)
    {
        switch ($transactionType) {
            case 'RC':
            case 'DB':
            case 'CP':
            case 'RB':
                return BESTELLUNG_STATUS_BEZAHLT;
                break;
            default:
                return null;
        }
    }

    private function loadOrder($response)
    {
        $bestellNr = $response->getIdentification()
            ->getTransactionId();

        $bestellungDB = Shop::DB()->select('tbestellung', 'cBestellNr', $bestellNr);
        if (!empty($bestellungDB)) {
            $order = new Bestellung($bestellungDB->kBestellung);
        } else {
            Jtllog::writeLog('heidelpay push-gw: No Order Found matching the response for ' . $bestellNr, JTLLOG_LEVEL_NOTICE);
            return null;
        }

        return $order;
    }

    private function addIncomingPayment($order, $incomingPayment)
    {
        if ($this->paymentExists($incomingPayment)) {
            return;
        }
        $this->paymentModule->addIncomingPayment($order, $incomingPayment);
    }

    private function paymentExists($incomingPayment)
    {
        $dbPayment = Shop::DB()->select('tzahlungseingang', 'cHinweis', $incomingPayment->cHinweis);
        if (empty($dbPayment)) {
            return false;
        }
        return true;
    }

    public function saveResponse()
    {
        $referenceId = $this->referenceExists()?$this->response->getIdentification()->getReferenceId():NULL;

        $dbResponse = new stdClass();
        $dbResponse->transaction_id = $this->response->getIdentification()->getTransactionId();
        $dbResponse->unique_id = $this->response->getIdentification()->getUniqueId();
        $dbResponse->reference_id = $referenceId;
        $dbResponse->timestamp = $this->response->getProcessing()->timestamp;

        $pluginTableName = 'xplugin_heidelpay_standard_push_notification';

        Jtllog::writeLog(print_r($dbResponse,1), JTLLOG_LEVEL_DEBUG);

        Shop::DB()->insert($pluginTableName, $dbResponse);
    }

    /*public function orderUpdate($updatedOrder)
    {
        Jtllog::writeLog('heidelpay push-gw: Order status updated for '
            . $this->response->getIdentification()->getTransactionId(), JTLLOG_LEVEL_NOTICE);

        Shop::DB()->update('tbestellung', 'kBestellung',
            $updatedOrder->kBestellung,
            $updatedOrder);
    }*/

    public function referenceExists()
    {
        $reference = $this->response->getIdentification()->getReferenceId();
        if (empty($reference)) {
            return false;
        }
        return true;
    }

    public function isTimeStampNew()
    {
        $previousPush = Shop::DB()->select('xplugin_heidelpay_standard_push_notification',
            'unique_id',
            $this->response->getIdentification()->getUniqueId())
        ;

        if($previousPush !== null ) {
            if($previousPush->timestamp < $this->response->getProcessing()->timestamp) {
                Jtllog::writeLog('TimestampCheck: previeous: '
                    .$previousPush->timestamp.' recent: '
                    .$this->response->getProcessing()->timestamp,
                    JTLLOG_LEVEL_DEBUG);
            } else {
                Jtllog::writeLog('TimestampCheck: recent push is older then the existing ',
                    JTLLOG_LEVEL_DEBUG);
            }
            return $previousPush->timestamp < $this->response->getProcessing()->timestamp;
        }
        return true;
    }
}