{* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright � 2016-present Heidelberger Payment GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo/Ronja Wann
* @category JTL
*}

<div id="otform">
<form method="post" class="formular" action="{$action_url}" id="paymentFrameForm">
	Bankland:<select name="ACCOUNT.COUNTRY">
		{foreach from=$account_country key=iso item=land }
		<option value="{$iso}">{$land}</option>
		{/foreach}
	</select><br/>
	Bankname<select name="ACCOUNT.BANKNAME">
		{foreach from=$account_bankname key=short item=brand}
			<option value="{$short}">{$brand}</option>
        {/foreach}
	</select><br/>

	<button type="submit">Submit data</button></td>
</form>

</div>