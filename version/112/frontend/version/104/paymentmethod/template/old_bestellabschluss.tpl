{* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo
* @category JTL
*}

<div style="margin:10px 0";>
	{if $status == 'error'}
		<strong>{$error}</strong>
	{else}
		{lang key="heidelpayDesc" section=""}
		{strip}
		<div>
			<a href="{$url}">
				<img src="{$currentTemplateDir}../../gfx/HeidelPay/logo.gif" " alt="HeidelPay Logo" />
			</a>
		</div>
		{/strip}
	{/if}
</div>