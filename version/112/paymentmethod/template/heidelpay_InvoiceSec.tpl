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


<div id="ivform">
	<div class="alert alert-info">{$paytext}</div>
	<form method="post" class="formular" action="{$action_url}" id="paymentFrameForm">
		<script type="text/javascript" src="./includes/plugins/heidelpay_standard/version/112/paymentmethod/template/js/birthDateAction.js"></script>
        {html_options name='NAME.SALUTATION' options=$salutation selected=$salutation_pre} <label class="control-label">&nbsp;{$holder}</label><br/>

		<br/>
            {if isset($birthdate)}
				<label class="control-label">{$birthdate_label}</label><br/>
                {assign var=payment_data value=$birthdate}
                {html_select_date|utf8_encode day_id='Date_Day' month_id='Date_Month' year_id='Date_Year' time=$payment_data start_year='-18' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
            {else}
                {html_select_date|utf8_encode day_id='Date_Day' month_id='Date_Month' year_id='Date_Year' start_year='-18' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
            {/if}

		<input type="hidden" name="NAME.BIRTHDATE" id="birthdate_papg" value=""><br/>
		<br/>
		<br/>
		<button class="submit btn btn-primary" type="submit">{$pay_button_label}</button></td>
	</form>

</div>