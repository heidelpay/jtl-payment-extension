{if $ddForm}
	{if $ddFormError != ''}
		<div class='box_error'>{$ddFormError}</div>
	{/if}
	
	<div id='ddForm'>
		<form autocomplete="off"  action="{$heidelpay_iframe}" method="post" name="ddForm">
			{literal}
			<style type="text/css">
				#ddForm label{ width: 110px; display: inline-block; margin: 4px 0; }
				#ddForm input{ width: 200px; }
				#ddForm select{ width: 203px; }
			</style>
			{/literal}
			
			{if ($ddFormSepa == 'both') || ($ddFormSepa == 'both_s')}
				{literal}
				<script type='text/javascript'>
					$(document).ready(function(){
						if(jQuery('#sepa_switch :selected').val() == 'iban'){ iban(); }
						if(jQuery('#sepa_switch :selected').val() == 'noiban'){ noiban(); }
						
						jQuery('#sepa_switch').change(function(){
							if(jQuery('#sepa_switch :selected').val() == 'iban'){ iban(); }
							if(jQuery('#sepa_switch :selected').val() == 'noiban'){ noiban(); }
						});
						
						function iban(){
							jQuery('#account').parent().hide();
							jQuery('#bankcode').parent().hide();
							jQuery('#iban').parent().show();
							jQuery('#bic').parent().show();
						}
						function noiban(){
							jQuery('#account').parent().show();
							jQuery('#bankcode').parent().show();
							jQuery('#iban').parent().hide();
							jQuery('#bic').parent().hide();
						}
					});
				</script>
				{/literal}
			
				<label>{$ddTxtSepa}:</label>
				<select id="sepa_switch" name="ddData[hpdd_sepa]">
					<option value="noiban">{$ddTxtSepaKtn}</option>
					<option value="iban">{$ddTxtSepaIban}</option>
				</select><br />
			{/if}
			
			<label>{$ddTxtBankCountry}:</label>
			<select id="bankCountry" name="ddData[ACCOUNT.COUNTRY]">
				{foreach key=iso item=country from=$avaBankCountrys}
					<option value="{$iso}">{$country}</option>
				{/foreach}
			</select><br />			
			
			{if ($ddFormSepa == 'classic') || ($ddFormSepa == 'both') || ($ddFormSepa == 'both_s')}
			<div>
				<label>{$ddTxtKtn}*:</label>
				<input type="text" class="text " value="" id="account" name="ddData[ACCOUNT.NUMBER]" /><br />
			</div>
			<div>
				<label>{$ddTxtBlz}*:</label>
				<input type="text" class="text " value="" id="bankcode" name="ddData[ACCOUNT.BANK]" /><br />
			</div>
			{/if}
			{if ($ddFormSepa == 'iban') || ($ddFormSepa == 'both') || ($ddFormSepa == 'both_s')}
			<div>
				<label>{$ddTxtIban}*:</label>
				<input type="text" class="text " value="" id="iban" name="ddData[ACCOUNT.IBAN]" /><br />
			</div>
			<div>
				<label>{$ddTxtBic}*:</label>
				<input type="text" class="text " value="" id="bic" name="ddData[ACCOUNT.BIC]" /><br />
			</div>
			{/if}
			
			<label>{$ddTxtHolder}*:</label>
			<input type="text" class="text " value="{if $ddFormHolder != ''}{$ddFormHolder}{/if}" id="accHolder" name="ddData[ACCOUNT.HOLDER]"><br /><br />
			<p>{$ddTxtMandatory}</p>
			
			{foreach key=pKey item=pItem from=$smarty.post}
				{if $pKey != 'ddData'}<input type="hidden" name="{$pKey}" value="{$pItem}" />{/if}
			{/foreach}

			<input type="submit" value="{$ddTxtButton}" />
		</form>
	</div>
{else}
	{$heidelpay_iframe}
{/if}