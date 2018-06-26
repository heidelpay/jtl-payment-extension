{* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright � 2016-present heidelpay GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo/Ronja Wann
* @category JTL
*}

<form method="post" class="formular" action="
<?php
    if ($iDeal->getResponse()->isSuccess()) {
        echo $iDeal->getResponse()->getPaymentFormUrl();
    }
?>
" id="paymentFrameForm">
	<?php
    if ($iDeal->getResponse()->isError()) {
	echo '<pre>'. print_r($iDeal->getResponse()->getError(),1).'</pre>';
	}
	?>
	Bankland:<select name="ACCOUNT.COUNTRY">
		<?php foreach ($iDeal->getResponse()->getConfig()->getBankCountry() AS $cKey => $cValue)
		echo '<option value="'.$cKey.'">'.$cValue.'</option>';
		?>
	</select><br/>
	Bankname<select name="ACCOUNT.BANKNAME">
		<?php foreach ($iDeal->getResponse()->getConfig()->getBrands() AS $cKey => $cValue)
		echo '<option value="'.$cKey.'">'.$cValue.'</option>';
		?>
	</select><br/>
	Holder:<input type="text" name="ACCOUNT.HOLDER" value="" /><br/>
	<button type="submit">Submit data</button></td>
</form>