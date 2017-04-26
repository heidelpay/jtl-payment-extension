<?php
/* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright � 2016-present Heidelberger Payment GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo
* @category JTL
*/

$args_arr['mailsmarty']->assign('billsafeinfo', 'Huhu Blalba');

//$args_arr['mail'];
//echo '<pre>'.print_r(get_defined_vars(), 1).'</pre>';
//if (class_exists('FB')) FB::log('Heidelpay Hook 153');
//if (class_exists('FB')) FB::log($args_arr['mail']);
if (class_exists('FB')) {
    FB::log($args_arr['mailsmarty']);
}
//if (class_exists('FB')) FB::log($args_arr['kEmailvorlage']);
//if (class_exists('FB')) FB::log($args_arr['kSprache']);
//if (class_exists('FB')) FB::log($args_arr['cPluginBody']);
//if (class_exists('FB')) FB::log($args_arr['Emailvorlage']);

//mail('eilers@heidelpay.de', 'DEBUG', print_r($_SESSION['Zahlungsart'],1));

if ($oPlugin->oPluginZahlungsmethode_arr[0]->kZahlungsart == $_SESSION['Zahlungsart']->kZahlungsart && $oPlugin->cPluginID == 'heidelpay_standard' && strpos($_SESSION['Zahlungsart']->cModulId, 'heidelpaybillsafeplugin') !== false) {
    $repl = array(
          '{AMOUNT}'        => $_POST['CRITERION_BILLSAFE_AMOUNT'],
          '{CURRENCY}'      => $_POST['CRITERION_BILLSAFE_CURRENCY'],
          '{ACC_OWNER}'     => $_POST['CRITERION_BILLSAFE_RECIPIENT'],
          '{ACC_BANKNAME}'  => $_POST['CRITERION_BILLSAFE_BANKNAME'],
          '{ACC_NUMBER}'    => $_POST['CRITERION_BILLSAFE_ACCOUNTNUMBER'],
          '{ACC_BANKCODE}'  => $_POST['CRITERION_BILLSAFE_BANKCODE'],
          '{ACC_BIC}'       => $_POST['CRITERION_BILLSAFE_BIC'],
          '{ACC_IBAN}'      => $_POST['CRITERION_BILLSAFE_IBAN'],
          '{SHORTID}'       => $_POST['CRITERION_BILLSAFE_REFERENCE'],
          '{LEGALNOTE}'     => $_POST['CRITERION_BILLSAFE_LEGALNOTE'],
          '{NOTE}'               => $_POST['CRITERION_BILLSAFE_NOTE'],
        );
  // Texte
  define('HP_SUCCESS_BILLSAFE', 'Ihre Transaktion war erfolgreich!

            Ueberweisen Sie uns den Betrag von {CURRENCY} {AMOUNT} auf folgendes Konto
            Bankname:      {ACC_BANKNAME}
            Kontoinhaber : {ACC_OWNER}
            Konto-Nr. :    {ACC_NUMBER}
            Bankleitzahl:  {ACC_BANKCODE}
            IBAN:          {ACC_IBAN}
            BIC:           {ACC_BIC}
            Geben sie bitte im Verwendungszweck UNBEDINGT die Identifikationsnummer
        {SHORTID}
        und NICHTS ANDERES an.');
    define('HP_LEGALNOTE_BILLSAFE', 'Bitte �berweisen Sie den ausstehenden Betrag {DAYS} Tage nach dem Sie �ber den Versand informiert wurden.');

    $bsData = strtr(HP_SUCCESS_BILLSAFE, $repl);
    $bsData.= ' '.$_POST['CRITERION_BILLSAFE_LEGALNOTE'].' ';
  //$bsData.= substr($_POST['CRITERION_BILLSAFE_NOTE'], 0, strlen($_POST['CRITERION_BILLSAFE_NOTE'])-11).' '.date('d.m.Y', mktime(0,0,0,date('m'),date('d')+$_POST['CRITERION_BILLSAFE_PERIOD'],date('Y'))).'.';
  $bsData.= preg_replace('/{DAYS}/', $_POST['CRITERION_BILLSAFE_PERIOD'], HP_LEGALNOTE_BILLSAFE);
    $infoText = $bsData;
    $infoHtml = nl2br(htmlentities($bsData));
    $args_arr['mail']->bodyText = preg_replace('/#billsafeinfo#/', $infoText, $args_arr['mail']->bodyText);
    $args_arr['mail']->bodyHtml = preg_replace('/#billsafeinfo#/', $infoHtml, $args_arr['mail']->bodyHtml);
} else {
    $info = '';
    $args_arr['mail']->bodyText = preg_replace('/#billsafeinfo#/', $info, $args_arr['mail']->bodyText);
    $args_arr['mail']->bodyHtml = preg_replace('/#billsafeinfo#/', $info, $args_arr['mail']->bodyHtml);
}
