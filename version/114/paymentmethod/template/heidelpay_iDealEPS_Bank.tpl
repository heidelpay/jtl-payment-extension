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

<div id="otform">
<form method="post" class="formular" action="{$action_url}" id="paymentFrameForm">
	{$hp_bank_country_label}: <select name="ACCOUNT.COUNTRY">
		{foreach from=$account_country key=iso item=land }
		<option value="{$iso}">{$land}</option>
		{/foreach}
	</select><br/>
	{$hp_bankname_label}; <select name="ACCOUNT.BANKNAME">
		{foreach from=$account_bankname key=short item=brand}
			<option value="{$short}">{$brand}</option>
        {/foreach}
	</select><br/>

	<button type="submit">{$pay_button_label}</button>
</form>

</div>