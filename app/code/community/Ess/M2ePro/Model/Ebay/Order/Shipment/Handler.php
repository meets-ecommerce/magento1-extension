<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Order_Shipment_Handler extends Ess_M2ePro_Model_Order_Shipment_Handler
{
    //########################################

    public function handle(Ess_M2ePro_Model_Order $order, Mage_Sales_Model_Order_Shipment $shipment)
    {
        if (!$order->isComponentModeEbay()) {
            throw new InvalidArgumentException('Invalid component mode.');
        }

        $trackingDetails = $this->getTrackingDetails($order, $shipment);

        if (!$order->getChildObject()->canUpdateShippingStatus($trackingDetails)) {
            return self::HANDLE_RESULT_SKIPPED;
        }

        if (empty($trackingDetails)) {

            return $order->getChildObject()->updateShippingStatus()
                ? self::HANDLE_RESULT_SUCCEEDED
                : self::HANDLE_RESULT_FAILED;
        }

        $itemsToShip = $this->getItemsToShip($order, $shipment);

        if (empty($itemsToShip) || count($itemsToShip) == $order->getItemsCollection()->getSize()) {

            return $order->getChildObject()->updateShippingStatus($trackingDetails)
                ? self::HANDLE_RESULT_SUCCEEDED
                : self::HANDLE_RESULT_FAILED;
        }

        $succeeded = true;
        foreach ($itemsToShip as $item) {
            if ($item->getChildObject()->updateShippingStatus($trackingDetails)) {
                continue;
            }

            $succeeded = false;
        }

        return $succeeded ? self::HANDLE_RESULT_SUCCEEDED : self::HANDLE_RESULT_FAILED;
    }

    //########################################

    private function getItemsToShip(Ess_M2ePro_Model_Order $order, Mage_Sales_Model_Order_Shipment $shipment)
    {
        $productTypesNotAllowedByDefault = array(
            Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
            Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
        );

        $items = array();
        $allowedItems = array();
        foreach ($shipment->getAllItems() as $shipmentItem) {
            /** @var $shipmentItem Mage_Sales_Model_Order_Shipment_Item */

            $orderItem = $shipmentItem->getOrderItem();
            $parentOrderItemId = $orderItem->getParentItemId();

            if (!is_null($parentOrderItemId)) {
                !in_array($parentOrderItemId, $allowedItems) && ($allowedItems[] = $parentOrderItemId);
                continue;
            }

            if (!in_array($orderItem->getProductType(), $productTypesNotAllowedByDefault)) {
                $allowedItems[] = $orderItem->getId();
            }

            $additionalData = $orderItem->getAdditionalData();
            $additionalData = is_string($additionalData) ? @unserialize($additionalData) : array();

            $itemId = $transactionId = null;
            $orderItemDataIdentifier = Ess_M2ePro_Helper_Data::CUSTOM_IDENTIFIER;

            if (isset($additionalData['ebay_item_id']) && isset($additionalData['ebay_transaction_id'])) {
                // backward compatibility with versions 5.0.4 or less
                $itemId = $additionalData['ebay_item_id'];
                $transactionId = $additionalData['ebay_transaction_id'];
            } elseif (isset($additionalData[$orderItemDataIdentifier]['items'])) {
                if (!is_array($additionalData[$orderItemDataIdentifier]['items'])
                    || count($additionalData[$orderItemDataIdentifier]['items']) != 1
                ) {
                    return null;
                }

                if (isset($additionalData[$orderItemDataIdentifier]['items'][0]['item_id'])) {
                    $itemId = $additionalData[$orderItemDataIdentifier]['items'][0]['item_id'];
                }
                if (isset($additionalData[$orderItemDataIdentifier]['items'][0]['transaction_id'])) {
                    $transactionId = $additionalData[$orderItemDataIdentifier]['items'][0]['transaction_id'];
                }
            }

            if (is_null($itemId) || is_null($transactionId)) {
                continue;
            }

            $item = Mage::helper('M2ePro/Component_Ebay')
                ->getCollection('Order_Item')
                ->addFieldToFilter('order_id', $order->getId())
                ->addFieldToFilter('item_id', $itemId)
                ->addFieldToFilter('transaction_id', $transactionId)
                ->getFirstItem();

            if (!$item->getId()) {
                continue;
            }

            $items[$orderItem->getId()] = $item;
        }

        $resultItems = array();
        foreach ($items as $orderItemId => $item) {
            if (!in_array($orderItemId, $allowedItems)) {
                continue;
            }

            $resultItems[] = $item;
        }

        return $resultItems;
    }

    //########################################
}