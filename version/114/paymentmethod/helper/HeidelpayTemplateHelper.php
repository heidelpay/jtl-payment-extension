<?php
/**
 * Created by PhpStorm.
 * User: David.Owusu
 * Date: 22.03.2018
 * Time: 15:40
 */

class HeidelpayTemplateHelper
{
    private $paymentMethod;

    public function __construct(heidelpay_standard $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function addFieldsets(Smarty $smarty, array $fieldset)
    {
        foreach ($fieldset as $fieldName) {
            $this->addTemplateVars($smarty, $fieldName);
        }
        $this->addTemplateVars($smarty, 'action_url');
    }

    public function addTemplateVars(Smarty $smarty, $fieldName) {
        $paymentObject = $this->paymentMethod->paymentObject;
        if(!empty($_SESSION['Kunde'])) {
            switch ($fieldName) {
                case 'holder':
                    $smarty->assign('holder_label', $this->paymentMethod->getHolderLabel());
                    $smarty->assign('holder', $_SESSION['Kunde']->cVorname . ' ' . $_SESSION['Kunde']->cNachname);
                    break;
                case 'account':
                    $smarty->assign('account_country', $paymentObject->getResponse()->getConfig()->getBankCountry());
                    $smarty->assign('account_bankname', $paymentObject->getResponse()->getConfig()->getBrands());
                    break;
                case 'action_url':
                    $smarty->assign('action_url', $paymentObject->getResponse()->getPaymentFormUrl());
                    break;
                case 'birthdate':
                    $smarty->assign('birthdate_label', $this->paymentMethod->getBirthdateLabel());
                    $smarty->assign('birthdate', str_replace('.', '-', $_SESSION['Kunde']->dGeburtstag));
                    break;
                case 'salutation':
                    $smarty->assign('salutation', $this->paymentMethod->getSalutationArray());
                    $smarty->assign('salutation_pre', $this->paymentMethod->getSalutation());
                    break;
                case 'is_PG':
                    $smarty->assign('is_PG', true);
                    break;
                case 'optin':
                case 'privacy':
                    $smarty->assign('privatepolicy_label', $this->paymentMethod->getPrivatePolicyLabel());
                    $optinText = $paymentObject->getResponse()->getConfig()->getOptinText();
                    $smarty->assign('optin', utf8_decode($optinText['optin']));
                    $smarty->assign('privacy_policy', utf8_decode($optinText['privacy_policy']));
                    break;
                default:
            }
        }

    }
}