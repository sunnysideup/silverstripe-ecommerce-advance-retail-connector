<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api;

use SilverStripe\Core\Config\Config;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\EcommerceDelivery\Modifiers\PickUpOrDeliveryModifier;
use Sunnysideup\EcommerceTax\Modifiers\GSTTaxModifier;

/*
 * A set of static functions to convert
 * SilverSripe Order data into the array data required by the AR API
 */

class OrderHelpers
{
    private static $shipping_product_item_id = 0;

    public static function get_line_items(?Order $order, ?int $branchId, ?int $workstationId)
    {
        $lineItems = [];
        $isTaxInclusive = EcommerceConfig::inst()->ShopPricesAreTaxExclusive ? false : true;
        $taxModifier = null;
        $shippingModifier = null;
        $orderItems = $order->OrderItems();

        //find out if there is a shiping cost that needs to be added as a line item
        $modifiers = $order->Modifiers();
        foreach ($modifiers as $modifier) {
            if (is_a($modifier, PickUpOrDeliveryModifier::class) && ! $shippingModifier) {
                $shippingModifier = $modifier;
            }
            if (is_a($modifier, GSTTaxModifier::class) && ! $taxModifier) {
                $taxModifier = $modifier;
            }
        }

        $lineNumber = 0;
        if ($orderItems->exists()) {
            $lineNumber = 1;
            foreach ($orderItems as $orderItem) {
                $lineItems[] = [
                    'branchId' => $branchId,
                    'workstationId' => $workstationId,
                    'customerOrderId' => 0, // what should we put here?
                    'lineNumber' => $lineNumber,
                    'itemId' => $orderItem->getInternalItemID(),
                    'description' => $orderItem->getBuyableMoreDetails(),
                    'quantity' => $orderItem->Quantity,
                    'unitPrice' => $orderItem->getUnitPrice(),
                    'unitCost' => 0, // what should we put here?
                    'tax' => round($taxModifier->getTotalTaxPerLineItem($orderItem)),
                    'isTaxInclusive' => $isTaxInclusive,
                    'note' => '',
                    'promoCode' => 0, //to be added
                    'lineReference' => 0, // what should we put here?
                    'purchaseOrderId' => 0, // what should we put here?
                    'takenQuantity' => 0, // what should we put here?
                    'totalAmount' => $orderItem->CalculatedTotal,
                ];
                ++$lineNumber;
            }
        }

        $shippingItemID = Config::inst()->get(OrderHelpers::class, 'shipping_product_item_id');

        if ($shippingItemID && $shippingModifier) {
            //find out if there is a shiping cost that needs to be added as a line item
            if ($shippingModifier->CalculatedTotal > 0) {
                $lineItems[] = [
                    'branchId' => $branchId,
                    'workstationId' => $workstationId,
                    'customerOrderId' => 0,
                    'lineNumber' => $lineNumber,
                    'itemId' => $shippingItemID, //what is the itemID for outside of NZ?
                    'description' => $shippingModifier->Name,
                    'quantity' => 1,
                    'unitPrice' => $shippingModifier->CalculatedTotal,
                    'unitCost' => 0,
                    'tax' => round($taxModifier->simpleTaxCalculation($shippingModifier->CalculatedTotal)),
                    'isTaxInclusive' => $isTaxInclusive,
                    'note' => '',
                    'promoCode' => 0,
                    'lineReference' => 0,
                    'purchaseOrderId' => 0,
                    'takenQuantity' => 0,
                    'totalAmount' => $shippingModifier->CalculatedTotal,
                ];
            }
        }

        return $lineItems;
    }

    public static function get_payments(?Order $order)
    {
        $payments = [];

        $orderPayments = $order->Payments();

        if ($orderPayments->exists()) {
            foreach ($orderPayments as $orderPayment) {
                $payments[] = [
                    'id' => '', //what should this be? - if it is more than one character we get this error: The field TenderId must be a string or array type with a maximum length of '1'
                    'description' => $orderPayment->Message,
                    'amount' => $orderPayment->AmountAmount,
                    'reference' => $orderPayment->Status, // or should it be the credit card reference?
                    'currencyCode' => $orderPayment->AmountCurrency,
                ];
            }
        }

        return $payments;
    }

    public static function get_address(?Order $order, ?string $addressType = 'BillingAddress')
    {
        //required fields
        $address = [
            'id' => 0,
            'name' => '',
            'address' => [
                '',
            ],
            'postCode' => '',
            'phone' => '',
            'extension' => '',
            'fax' => '',
            'mobile' => '',
            'email' => '',
            'dpid' => '',
            'barcode' => '',
            'narrative' => '',
        ];

        if ('ShippingAddress' === $addressType && ! $order->UseShippingAddress) {
            $addressType = 'BillingAddress';
        }

        $orderAddress = $order->{$addressType}();

        if ($orderAddress && $orderAddress->exists()) {
            $address = [
                'id' => 0, //should this come from AR?
                'name' => $orderAddress->FirstName . ' ' . $orderAddress->Surname,
                'address' => [
                    $orderAddress->Address,
                    $orderAddress->Address2,
                    $orderAddress->City,
                    $orderAddress->Country,
                ],
                'postCode' => $orderAddress->PostalCode,
                'phone' => $orderAddress->Phone,
                'extension' => '',
                'fax' => '',
                'mobile' => '',
                'email' => $orderAddress->Email,
                'dpid' => '',
                'barcode' => '',
                'narrative' => '',
            ];
        }

        return $address;
    }
}
