<?php
/**
 * Created by PhpStorm.
 * User: Florian.Evertz
 * Date: 02.02.2018
 * Time: 12:09
 */

if (isset($_GET['disableInvoice']) && $_GET['disableInvoice']) {
    $_SESSION['InvoiceDisabled'] = true;
}

if ($_SESSION['InvoiceDisabled']) {
    $zahlungsArray = Shop::Smarty()->getTemplateVars('Zahlungsarten', null, false);

    //Search for heidelpay Secured Invoice in smarty Object and remove it
    foreach ($zahlungsArray as $key => $class) {
        if ($class->cModulId == 'kPlugin_10_heidelpaygesicherterechnungplugin') {
            unset($zahlungsArray[$key]);
            Shop::Smarty()->assign('Zahlungsarten', $zahlungsArray);
        }
    }
}
