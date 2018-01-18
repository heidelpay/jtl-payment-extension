/**
 *  SUMMARY
 *
 * DESC
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright � 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author Ronja Wann
 * @category JTL
 */


paymentFrameForm = document.getElementById('paymentFrameForm');

function formatDate(e){

$("#birthdate_papg").val((document.getElementById('Date_Year').value) + "-" + (document.getElementById('Date_Month').value) + "-" + (document.getElementById('Date_Day').value));

}


if (paymentFrameForm.addEventListener) {// W3C DOM
    paymentFrameForm.addEventListener('submit', formatDate); }
else if (paymentFrameForm.attachEvent) { // IE DOM
    paymentFrameForm.attachEvent('onsubmit', formatDate);
}
