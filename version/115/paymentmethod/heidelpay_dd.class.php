<?php
/*
 * Direct debit paymentmethod
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once $oPlugin->cPluginPfad . 'paymentmethod/heidelpay_standard.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods\DirectDebitPaymentMethod;

class heidelpay_dd extends heidelpay_standard
{

    public function setPaymentObject()
    {
        $this->paymentObject = new DirectDebitPaymentMethod();
    }

    public function sendPaymentRequest()
    {
        $this->paymentObject->debit();
    }

    public function setInfoContent($args)
    {
        $mailingObject = new stdClass();
        $mailingObject->accIdent = $args ['ACCOUNT_IDENTIFICATION'];
        $mailingObject->amount = $args ['PRESENTATION_AMOUNT'];
        $mailingObject->currency = $args ['PRESENTATION_CURRENCY'];

        if (isset($args ['IDENTIFICATION_CREDITOR_ID']) && ($args ['IDENTIFICATION_CREDITOR_ID'] != '')) {
            $mailingObject->identCreditor  = $args ['IDENTIFICATION_CREDITOR_ID'];
        } else {
            $mailingObject->identCreditor  = '-';
        }

        return $mailingObject;
    }

    public function getInfoTemplateId()
    {
        return 'hp-dd-reminder';
    }
}
