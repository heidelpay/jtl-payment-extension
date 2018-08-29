<?php
/*
 * Helper to create the basket object.
 *
 * For some paymentmethods basket is required to improve acceptance rate of insurance provider.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/JTL
 * @author David Owusu
 * @category JTL
 */
require_once PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cVerzeichnis . ''.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
require_once PFAD_ROOT . PFAD_CLASSES . "class.JTL-Shop.Jtllog.php";

use Heidelpay\PhpBasketApi\Object\Authentication;
use Heidelpay\PhpBasketApi\Object\Basket;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use Heidelpay\PhpBasketApi\Request;
use Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException;
use Heidelpay\PhpBasketApi\Response;


/**
 * Class HeidelpayBasketHelper
 */
class HeidelpayBasketHelper
{
    /**
     * Prepare the basket object from order and perform the request to transmit the basket to the heidelpay payment.
     * @param Bestellung $order
     * @param array $oPluginSettings
     * @param boolean $isSandbox indicate whether the request is running in test mode or not .
     * @return Response If successful the response will contain a basket ID that can be added to the transaction request
     */
    public static function sendBasketFromOrder( Bestellung $order, $oPluginSettings , bool $isSandbox)
    {
        try {
            $authentication = new Authentication(
                trim($oPluginSettings ['user']),
                trim($oPluginSettings ['pass']),
                trim($oPluginSettings ['sender'])
            );
        } catch (\Exception $exception) {
            Jtllog::writeLog('heidelpay error: basked could not be added. Message: '
                . $exception->getMessage(), JTLLOG_LEVEL_ERROR, false);
            return new Response();
        }

        $basket = new Basket();
        $request = new Request($authentication, $basket);
        $request->setIsSandboxMode($isSandbox);

        $basket->setCurrencyCode($order->Waehrung->cISO);
        $basket->setBasketReferenceId($order->cBestellNr);

        // Add all order elements to the basket
        foreach ($order->Positionen as $position) {
            $item = new BasketItem();
            self::mapToItem($position, $item);
            $item->setBasketItemReferenceId($basket->getItemCount() + 1);
            try {
                $basket->addBasketItem($item, null, true);
            } catch ( InvalidBasketitemPositionException $exception) {
                Jtllog::writeLog($exception->getMessage(), JTLLOG_LEVEL_ERROR, false);
            }
        }
        return $request->addNewBasket();
    }

    /**
     * Map the product information to the corresponding attributes of the basket item.
     * @param WarenkorbPos $position
     * @param BasketItem $item
     */
    private static function mapToItem($position, BasketItem $item)
    {
        //calculate values
        $unit = $position->Artikel->cMasseinheitCode;
        $articleId = !empty($position->Artikel->kArtikel) ? $position->Artikel->kArtikel : '';
        $vat = (int)$position->fMwSt;
        $type = self::findItemType($position->nPosTyp);
        $amountPerUnit = (int)round(bcmul($position->fPreisEinzelNetto, (100 + $vat), 1));
        $amountGross = (int)bcmul($amountPerUnit, $position->nAnzahl, 1);
        $amountNet = (int)bcmul(round(bcmul($position->fPreisEinzelNetto, 100, 1)), $position->nAnzahl);
        $amountVat = $amountGross - $amountNet;

        // Set basket values
        $item->setTitle($position->cName);
        $item->setUnit($unit);
        $item->setType($type);
        $item->setQuantity($position->nAnzahl);
        $item->setArticleId($articleId);
        $item->setVat($vat);

        $item->setAmountPerUnit($amountPerUnit);
        $item->setAmountGross($amountGross);
        $item->setAmountNet($amountNet);
        $item->setAmountVat($amountVat);
    }

    /**
     * Find the correct type depending on the posType number
     * @param int $posType
     * @return string
     */
    private static function findItemType($posType)
    {
        switch ((string)$posType) {
            case C_WARENKORBPOS_TYP_ARTIKEL:
                return 'goods';
            case C_WARENKORBPOS_TYP_VERSANDPOS:
                return 'shipping';
            case C_WARENKORBPOS_TYP_GUTSCHEIN:
            case C_WARENKORBPOS_TYP_KUPON:
                return 'voucher';
            default:
                return 'other';
        }
    }
}
