/**
 *  SUMMARY
 *
 * DESC
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright � 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author Ronja Wann
 * @category JTL
 */


paymentFrameForm = document.getElementById('paymentFrameForm');

function u18Check() {
    var birthdate = new Date($("#birthdate_papg").val());
    var currentDate = new Date;
    return new Date(currentDate-birthdate).getFullYear() - new Date(0).getFullYear() < 18
}

function formatDate(e) {
    var input = $("#birthdate_papg");
    input.val((document.getElementById('Date_Year').value) + "-" + (document.getElementById('Date_Month').value) + "-" + (document.getElementById('Date_Day').value));

    if (u18Check()) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        else {
            e.returnValue = false;
        }
        $("#hp_minimum_age")
            .removeClass('hidden-initial')
            .addClass('alert alert-danger');
    }
}

if (paymentFrameForm.addEventListener) {// W3C DOM
    paymentFrameForm.addEventListener('submit', formatDate); }
else if (paymentFrameForm.attachEvent) { // IE DOM
    paymentFrameForm.attachEvent('onsubmit', formatDate);
}
