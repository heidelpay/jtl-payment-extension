<?php
/*
 * Prepayment card paymentmethod
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once $oPlugin->cPluginPfad . 'paymentmethod/heidelpay_standard.class.php';

use Heidelpay\PhpPaymentApi\PaymentMethods\PrepaymentPaymentMethod;

class heidelpay_pp extends heidelpay_standard
{

    public function setPaymentObject()
    {
        $this->paymentObject = new PrepaymentPaymentMethod();
    }

    public function setInfoContent($args)
    {
        $mailingObject = new stdclass();
        $mailingObject->accIban = $args ['CONNECTOR_ACCOUNT_IBAN'];
        $mailingObject->accBic = $args ['CONNECTOR_ACCOUNT_BIC'];
        $mailingObject->accHolder = $args ['CONNECTOR_ACCOUNT_HOLDER'];
        $mailingObject->amount = $args ['PRESENTATION_AMOUNT'];
        $mailingObject->currency = $args ['PRESENTATION_CURRENCY'];
        $mailingObject->usage = $args ['IDENTIFICATION_SHORTID'];

        return $mailingObject;
    }

    /**
     * @return string
     */
    public function getInfoTemplateId()
    {
        return 'hp-pp-reminder';
    }
}
