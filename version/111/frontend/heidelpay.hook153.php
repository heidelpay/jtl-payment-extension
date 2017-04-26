<?php
/* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo
* @category JTL
*/


$args_arr['mailsmarty']->assign('billsafeinfo', 'Test');

if (class_exists('FB')) {
    FB::log($args_arr['mailsmarty']);
}

if (!empty($_POST['PROCESSING_RESULT'])) {
    if ($oPlugin->cPluginID == 'heidelpay_standard' && strpos($_SESSION['Zahlungsart']->cModulId, 'heidelpaybillsafeplugin') !== false) {
        $bankName            = $_POST['CRITERION_BILLSAFE_BANKNAME'];
        $kontoInhaber            = $_POST['CRITERION_BILLSAFE_RECIPIENT'];
        $iban                = $_POST['CRITERION_BILLSAFE_IBAN'];
        $bic                    = $_POST['CRITERION_BILLSAFE_BIC'];
        $betrag                = str_replace(".", ",", sprintf("%01.2f", $_POST['CRITERION_BILLSAFE_AMOUNT']));
        $waehrung            = $_POST['CRITERION_BILLSAFE_CURRENCY'];
        $billsafeLegalnote    = $_POST['CRITERION_BILLSAFE_LEGALNOTE'];    // Wird noch nicht benutzt
          $billsafeNote        = htmlentities($_POST['CRITERION_BILLSAFE_NOTE'], ENT_NOQUOTES, 'UTF-8');
        $billsafePdfUrl        = $_POST['CRITERION_BILLSAFE_PDF_URL'];        // Wird noch nicht benutzt
          $billsafeReference    = $_POST['CRITERION_BILLSAFE_REFERENCE'];
           
        $stringDe = "
		  Bitte &uuml;berweisen Sie uns den Betrag von <strong>$betrag  $waehrung</strong> auf folgendes Konto:<br/><br/>
		  Bankname:			$bankName<br/>
		  Kontoinhaber: 	$kontoInhaber<br/>
		  IBAN: 			$iban<br/>
		  BIC: 				$bic<br/><br/>
		  <i>Geben Sie als Verwendungszweck bitte ausschlie&szlig;lich diese Identifikationsnummer an:</i><br/>
		  <strong>$billsafeReference</strong><br/><br/>
		  $billsafeNote<br/>";
           
        $stringEn = "
		  Please transfer the amount of <strong>$betrag $waehrung</strong> to the following account<br /><br />
		  Bankname:		$bankName<br/>
		  Holder: 		$kontoInhaber<br/>
		  IBAN: 		$iban<br/>
		  BIC: 			$bic<br/><br/>
		  <i>Please use only this identification number as the descriptor :</i><br/>
		  <strong>$billsafeReference</strong><br/>
		  $billsafeNote<br/>";
           
        $ausgabeInEmail = '';
        if (strtolower($_POST['FRONTEND_LANGUAGE']) == 'de') {
            $ausgabeInEmail = $stringDe;
        } else {
            $ausgabeInEmail = $stringEn;
        }
           
        $args_arr['mail']->bodyText = preg_replace('/#billsafeinfo#/', $ausgabeInEmail, $args_arr['mail']->bodyText);
        $args_arr['mail']->bodyHtml = preg_replace('/#billsafeinfo#/', $ausgabeInEmail, $args_arr['mail']->bodyHtml);
    } elseif ($oPlugin->cPluginID == 'heidelpay_standard' && strpos($_SESSION['Zahlungsart']->cModulId, 'heidelpayrechnungplugin') !== false) {
        // Case Rechnungskauf / Invoice
        $datenArray['betrag']        = str_replace(".", ",", sprintf("%01.2f", $_POST['PRESENTATION_AMOUNT']));
        $datenArray['waehrung']    = $_POST['PRESENTATION_CURRENCY'];
        ;
        $datenArray['kontoInhaber'] = $_POST['CONNECTOR_ACCOUNT_HOLDER'];
        $datenArray['iban']            = $_POST['CONNECTOR_ACCOUNT_IBAN'];
        $datenArray['bic']            = $_POST['CONNECTOR_ACCOUNT_BIC'];
        $datenArray['vZweck']        = $_POST['IDENTIFICATION_SHORTID'];
        $datenArray['lang']            = $_POST['FRONTEND_LANGUAGE'];
        
        $args_arr['mail']->bodyText = preg_replace('/#billsafeinfo#/', returnEmailStr($datenArray), $args_arr['mail']->bodyText);
        $args_arr['mail']->bodyHtml = preg_replace('/#billsafeinfo#/', returnEmailStr($datenArray), $args_arr['mail']->bodyHtml);
    } elseif ($oPlugin->cPluginID == 'heidelpay_standard' && strpos($_SESSION['Zahlungsart']->cModulId, 'heidelpayvorkasseplugin') !== false) {
        // case Vorkasse / Prepayment
        $datenArray['betrag']        = str_replace(".", ",", sprintf("%01.2f", $_POST['PRESENTATION_AMOUNT']));
        $datenArray['waehrung']    = $_POST['PRESENTATION_CURRENCY'];
        ;
        $datenArray['kontoInhaber'] = $_POST['CONNECTOR_ACCOUNT_HOLDER'];
        $datenArray['iban']            = $_POST['CONNECTOR_ACCOUNT_IBAN'];
        $datenArray['bic']            = $_POST['CONNECTOR_ACCOUNT_BIC'];
        $datenArray['vZweck']        = $_POST['IDENTIFICATION_SHORTID'];
        $datenArray['lang']            = $_POST['FRONTEND_LANGUAGE'];
        
        $args_arr['mail']->bodyText = preg_replace('/#billsafeinfo#/', returnEmailStr($datenArray), $args_arr['mail']->bodyText);
        $args_arr['mail']->bodyHtml = preg_replace('/#billsafeinfo#/', returnEmailStr($datenArray), $args_arr['mail']->bodyHtml);
    } else {
        $args_arr['mail']->bodyText = preg_replace('/#billsafeinfo#/', '', $args_arr['mail']->bodyText);
        $args_arr['mail']->bodyHtml = preg_replace('/#billsafeinfo#/', '', $args_arr['mail']->bodyHtml);
    }
} else {
    $args_arr['mail']->bodyText = preg_replace('/#billsafeinfo#/', '', $args_arr['mail']->bodyText);
    $args_arr['mail']->bodyHtml = preg_replace('/#billsafeinfo#/', '', $args_arr['mail']->bodyHtml);
}


/** returnEmailStr()
 *
 * gibt den String zurück, der in der Bestellbestätigung an den Kunden geht
 * bei Sprache de wird deustcher Text ausgegeben, bei anderen Sprachen en Text
 *
 * @param array $datenArray
 * 	$datenArray['betrag'] 		=> Betrag formatiert,
 *  $datenArray['waehrung'] 	=> ISO-Konform,
 *  $datenArray['kontoInhaber'] => Kontoinhaber laut Payment,
 *  $datenArray['iban']			=> IBAN laut Payment,
 *  $datenArray['bic']			=> BIC laut Payment,
 *  $datenArray['vZweck']		=> Short-ID laut Payment,
 *  $datenArray['lang']			=> FRONTEND_LANGUAGE
 */
function returnEmailStr($datenArray)
{
    $stringDe = "
	Bitte &uuml;berweisen Sie uns den Betrag von <strong>".$datenArray['betrag']." ".$datenArray['waehrung']."</strong> auf folgendes Konto:<br/><br/>
	Kontoinhaber: 	".$datenArray['kontoInhaber']."<br/>
	IBAN: 			".$datenArray['iban']."<br/>
	BIC: 			".$datenArray['bic']."<br/><br/>
	<i>Geben Sie als Verwendungszweck bitte ausschlie&szlig;lich diese Identifikationsnummer an:</i><br/>
	<strong>".$datenArray['vZweck']."</strong><br/><br/>";

    $stringEn = "
	Please transfer the amount of <strong>".$datenArray['betrag']." ".$datenArray['waehrung']."</strong> to the following account<br /><br />
	Holder: 		".$datenArray['kontoInhaber']."<br/>
	IBAN: 			".$datenArray['iban']."<br/>
	BIC: 			".$datenArray['bic']."<br/><br/>
	<i>Please use only this identification number as the descriptor :</i><br/>
	<strong>".$datenArray['vZweck']."</strong>";

    $ausgabeInEmail = '';
    if (strtolower($datenArray['lang']) == 'de') {
        $ausgabeInEmail = $stringDe;
    } else {
        $ausgabeInEmail = $stringEn;
    }

    return $ausgabeInEmail;
}
