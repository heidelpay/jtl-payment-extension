<?

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

echo 1;
require_once("includes/globalinclude.php");
require_once(PFAD_ROOT . PFAD_CLASSES . "class.JTL-Shop.Plugin.php");
require_once(PFAD_ROOT . PFAD_INCLUDES . "bestellabschluss_inc.php");
require_once(PFAD_ROOT . PFAD_INCLUDES . "bestellvorgang_inc.php");
#require_once(PFAD_ROOT . PFAD_INCLUDES . "trustedshops_inc.php");
#require_once(PFAD_ROOT . PFAD_INCLUDES . "mailTools.php");
$AktuelleSeite = "BESTELLVORGANG";
//session starten

ini_set('display_errors', 1);

$session = new Session();
$sid = session_name().'='.session_id();
/*
$bestellung = $_SESSION['bestellung'];
#$bestellung = fakeBestellung();
#$bestellung = new Bestellung();
#$bestellung = new Bestellung($bestellid->kBestellung);
#$bestellung->fuelleBestellung(0);
require_once(PFAD_INCLUDES_MODULES."heidelpay/HeidelPay.class.php");
$paymentMethod = new HeidelPay($_SESSION['Zahlungsart']->cModulId);
$paymentMethod->cModulId = $_SESSION['Zahlungsart']->cModulId;
$successURL = $paymentMethod->getReturnURL($bestellung);
 */
$successURL = 'bestellabschluss.php?i='.$_GET['order_id'];

echo '<pre>'.print_r($_GET, 1).'</pre>';
echo '<pre>'.print_r($_POST, 1).'</pre>';
echo '<pre>'.print_r($_SESSION, 1).'</pre>';
#exit();
#include_once(PFAD_ROOT.PFAD_INCLUDES_MODULES.'heidelpay/class.heidelpay.php');
#$hp = new heidelpay();
$base = URL_SHOP.'/';

if (!empty($_SESSION['hpLastPost'])){
  $_POST = $_SESSION['hpLastPost'];
  $_SESSION['hpUniqueID'] = $_GET['uniqueId'];
  $next = 'bestellvorgang.php?hp_go=1';
  #if ($_SESSION['hpModuleMode'] == 'AFTER') $next = 'heidelpay_success.php?hp_go=1';
  if ($_SESSION['hpModuleMode'] == 'AFTER') $next = $successURL;

  if ($_GET['pcode'] == 'PP.PA'){
    $repl = array(
      '{AMOUNT}'        => $_GET['PRESENTATION_AMOUNT'], 
      '{CURRENCY}'      => $_GET['PRESENTATION_CURRENCY'], 
      '{ACC_COUNTRY}'   => $_GET['CONNECTOR_ACCOUNT_COUNTRY'], 
      '{ACC_OWNER}'     => $_GET['CONNECTOR_ACCOUNT_HOLDER'], 
      '{ACC_NUMBER}'    => $_GET['CONNECTOR_ACCOUNT_NUMBER'], 
      '{ACC_BANKCODE}'  => $_GET['CONNECTOR_ACCOUNT_BANK'],
      '{ACC_BIC}'       => $_GET['CONNECTOR_ACCOUNT_BIC'], 
      '{SHORTID}'       => $_GET['IDENTIFICATION_SHORTID'],
    );
    $_SESSION['hpPrepaidData'] = $repl;
  } else {
    $_SESSION['hpPrepaidData'] = false;
  }
  if (!empty($_GET['cancel'])) $next = 'heidelpay_fail.php?cancel=1';
  if (!empty($_GET['hperror'])) $next = 'heidelpay_fail.php?hperror='.$_GET['hperror'];
  #echo '<pre>'.print_r($_SESSION, 1).'</pre>';
  $next.= '&'.$sid; // Session anh�ngen

  #echo '<pre>'.print_r($_SESSION, 1).'</pre>';
?>
<html><head><title>Heidelpay Redirect</title></head>
<body onLoad="document.forms[0].submit()"><center>
<br><br><br><br><br>
<h2>Ihre Daten werden &uuml;bertragen...</h2><br>
<img src="<?=$base?>bilder/ladebalken.gif">
<form action="<?=$base.$next?>" method="post" style="display: none" target="_top">
<?foreach($_POST AS $k => $v){?>
  <?if (is_array($v)){?>
    <input type="text" name="<?=$k?>[<?=key($v)?>]" value="<?=current($v)?>">
  <?} else {?>
    <input type="text" name="<?=$k?>" value="<?=$v?>">
  <?}?>
<?}?>
</form>
</center>
</body>
</html>
<?}