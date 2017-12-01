<?php

/* SUMMARY
*
* DESC
*
* @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
* @copyright Copyright � 2016-present Heidelberger Payment GmbH. All rights reserved.
* @link https://dev.heidelpay.de/JTL
* @author Andreas Nemet/Jens Richter/Marijo Prskalo
* @category JTL
*/

if (!empty($_SESSION['heidelpayLastError'])) {
    $tmp = '<div style="border: 2px solid #f00; background-color: #fff; padding: 5px">Error:<br>'.$_SESSION['heidelpayLastError'].'</div>';
    pq("#content")->prepend($tmp);
    unset($_SESSION['heidelpayLastError']);
} elseif (!empty($_GET['hperror'])) {
    $tmp = '<div style="border: 2px solid #f00; background-color: #fff; padding: 5px">Error:<br>'.$_GET['hperror'].'</div>';
    pq("#content")->prepend($tmp);
} elseif (!empty($_GET['nHinweis'])) {
    $tmp = '<div style="border: 2px solid #f00; background-color: #fff; padding: 5px">Error:<br>'.$_GET['nHinweis'].'</div>';
    pq("#content")->prepend($tmp);
}
