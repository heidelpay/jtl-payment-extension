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
<div id="ccform">
	<div class="alert alert-info">{$paytext}</div>
    <div id="cardFrame-error" ></div>
    <form method="post" class="formular" id="paymentFrameForm">
        <iframe id="paymentIframe" src="{$action_url}" style="height:250px;" frameborder="0"></iframe><br />
        <button class="submit btn btn-primary" type="submit">{$pay_button_label}</button>
    </form>
    <script type="text/javascript" src="{$oPlugin->cFrontendPfadURL}js/creditCardFrame.js"></script>
</div>