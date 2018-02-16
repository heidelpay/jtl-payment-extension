{* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright � 2016-present Heidelberger Payment GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo
* @category JTL
*}

{config_load file="$lang.conf" section="global"}
{include file='tpl_inc/header.tpl'}
	<div id="wrapper">
		<div id="content">
			<div id="contentmid">
				<div id="content_head">
					<h1>{$title}</h1>
				</div>
				<div class="seite"><br>
				{$heidelpay_iframe}
				<br>
				</div>
			</div>
		</div>
	</div>
{include file='tpl_inc/leftContainer.tpl'}
{include file='tpl_inc/rightContainer.tpl'}
{include file='tpl_inc/footer.tpl'}