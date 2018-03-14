<?php
/**
 * Created by PhpStorm.
 * User: David.Owusu
 * Date: 08.03.2018
 * Time: 17:10
 */
require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/vendor/autoload.php';
require_once PFAD_ROOT . PFAD_CLASSES . "class.JTL-Shop.Jtllog.php";

use Heidelpay\PhpBasketApi\Object\Basket;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use Heidelpay\PhpBasketApi\Request;
use Heidelpay\PhpBasketApi\Object\Authentication;


class HeidelpayBasketHelper
{

    /**
     * @param Bestellung $order
     * @param $oPluginSettings
     * @return \Heidelpay\PhpBasketApi\Response
     */
    public static function sendBasketFromOrder($order, $oPluginSettings)
    {
        $authentication = new Authentication(
            $oPluginSettings ['user'],
            $oPluginSettings ['pass'],
            $oPluginSettings ['sender']
        );

        $basket = new Basket();
        $request = new Request($authentication, $basket);

        $basket->setCurrencyCode($order->Waehrung->cISO);
        $basket->setBasketReferenceId($order->cBestellNr);

        //add products to the basket. Shipment and coupons behave the same w
        foreach($order->Positionen as $position) {
            $item = new BasketItem();
            self::mapToItem($position, $item);
            $item->setBasketItemReferenceId($basket->getItemCount() + 1);
            $basket->addBasketItem($item, null, true);
        }

        //mail('david.owusu@heidelpay.de', 'Basket-Data', print_r($basket, 1));
        Jtllog::writeLog(print_r($basket, 1), JTLLOG_LEVEL_DEBUG);
        return $request->addNewBasket($basket);
    }

    private static function mapToItem($position, BasketItem $item)
    {
        //calculate Values
        $unit = self::fetchUnit($position->Artikel->kMassEinheit);
        $vat = (int)$position->fMwSt;
        $type = self::findItemType($position->nPosTyp);
        $amountPerUnit = (int)round(bcmul($position->fPreisEinzelNetto,(100+$vat), 3));
        $amountGross = (int)bcmul($amountPerUnit , $position->nAnzahl,3);
        $amountNet = (int)bcmul(round(bcmul($position->fPreis, 100, 3)) , $position->nAnzahl);
        $amountVat = $amountGross - $amountNet;

        // Set basket values
        $item->setTitle($position->cName);
        $item->setUnit($unit);
        $item->setQuantity($position->nAnzahl);
        $item->setArticleId(!empty($position->Artikel->kArtikel)?$position->Artikel->kArtikel:'');
        $item->setVat($vat);
        $item->setType($type);
        $item->setAmountPerUnit($amountPerUnit);
        $item->setAmountGross($amountGross);

        $item->setAmountNet($amountNet);
        $item->setAmountVat($amountVat);
    }

    private static function fetchUnit($unitId)
    {
        $query = 'SELECT cName FROM `tmasseinheitsprache` 
            WHERE `kMassEinheit` = '.$unitId;
        $myUnit = $GLOBALS['DB']->executeQuery($query, 1);
        if (!empty($myUnit->cName)) {
            return $myUnit->cName;
        }
        return null;
    }

    private static function findItemType($posType)
    {
        switch ((string)$posType) {
            case C_WARENKORBPOS_TYP_VERSANDPOS:
                return 'shipment';
            default:
                return 'goods';
        }
    }
}
