<?php
/**
 * Created by PhpStorm.
 * User: Florian.Evertz
 * Date: 02.02.2018
 * Time: 12:09
 */

if (array_key_exists('disableInvoice', $_GET) && $_GET['disableInvoice']) {
    $_SESSION['InvoiceDisabled'] = true;
}

if ($_SESSION['InvoiceDisabled']) {
    $zahlungsArray = Shop::Smarty()->getTemplateVars('Zahlungsarten', null, false);

    $hpPluginPatter = '/kPlugin_[0-9]+_heidelpaygesicherterechnungplugin/';
    //Search for heidelpay Secured Invoice in smarty Object and remove it
    foreach ($zahlungsArray as $key => $class) {
        if (preg_match($hpPluginPatter, $class->cModulId)) {
            unset($zahlungsArray[$key]);
            Shop::Smarty()->assign('Zahlungsarten', $zahlungsArray);
        }
    }
}
