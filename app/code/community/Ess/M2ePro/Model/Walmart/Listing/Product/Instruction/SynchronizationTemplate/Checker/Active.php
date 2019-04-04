<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

use Ess_M2ePro_Model_Magento_Product_ChangeProcessor_Abstract as ProductChangeProcessor;

class Ess_M2ePro_Model_Walmart_Listing_Product_Instruction_SynchronizationTemplate_Checker_Active
    extends Ess_M2ePro_Model_Walmart_Listing_Product_Instruction_SynchronizationTemplate_Checker_Abstract
{
    //########################################

    private function getStopInstructionTypes()
    {
        return array(
            Ess_M2ePro_Model_Walmart_Template_Synchronization_ChangeProcessor::INSTRUCTION_TYPE_STOP_MODE_ENABLED,
            Ess_M2ePro_Model_Walmart_Template_Synchronization_ChangeProcessor::INSTRUCTION_TYPE_STOP_MODE_DISABLED,
            Ess_M2ePro_Model_Walmart_Template_Synchronization_ChangeProcessor::INSTRUCTION_TYPE_STOP_SETTINGS_CHANGED,
            ProductChangeProcessor::INSTRUCTION_TYPE_PRODUCT_QTY_DATA_POTENTIALLY_CHANGED,
            ProductChangeProcessor::INSTRUCTION_TYPE_PRODUCT_STATUS_DATA_POTENTIALLY_CHANGED,
            Ess_M2ePro_Model_Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            Ess_M2ePro_Model_Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            Ess_M2ePro_Model_Walmart_Listing_Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
            Ess_M2ePro_Model_Walmart_Listing_Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
            Ess_M2ePro_Model_Walmart_Template_ChangeProcessor_Abstract::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
            Ess_M2ePro_Model_PublicServices_Product_SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            Ess_M2ePro_Model_PublicServices_Product_SqlChange::INSTRUCTION_TYPE_STATUS_CHANGED,
            Ess_M2ePro_Model_PublicServices_Product_SqlChange::INSTRUCTION_TYPE_QTY_CHANGED,
            ProductChangeProcessor::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            Ess_M2ePro_Model_Cron_Task_Listing_Product_InspectDirectChanges::INSTRUCTION_TYPE,
        );
    }

    //########################################

    public function isAllowed()
    {
        if (!$this->input->hasInstructionWithTypes($this->getStopInstructionTypes()) &&
            !$this->input->hasInstructionWithTypes($this->getReviseInstructionTypes())
        ) {
            return false;
        }

        $listingProduct = $this->input->getListingProduct();

        if (!$listingProduct->isStoppable() && !$listingProduct->isRevisable()) {
            return false;
        }

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        if (!$walmartListingProduct->isExistCategoryTemplate()) {
            return false;
        }

        $variationManager = $walmartListingProduct->getVariationManager();
        if ($variationManager->isVariationProduct()) {

            if ($variationManager->isRelationParentType()) {
                return false;
            }
        }

        return true;
    }

    //########################################

    public function process(array $params = array())
    {
        $scheduledAction = $this->input->getScheduledAction();
        if (is_null($scheduledAction)) {
            $scheduledAction = Mage::getModel('M2ePro/Listing_Product_ScheduledAction');
        }

        if ($this->input->hasInstructionWithTypes($this->getStopInstructionTypes())) {
            if (!$this->isMeetStopRequirements()) {
                if ($scheduledAction->isActionTypeStop() && !$scheduledAction->isForce()) {
                    $this->getScheduledActionManager()->deleteAction($scheduledAction);
                    $scheduledAction->unsetData();
                }
            } else {

                if ($scheduledAction->isActionTypeRevise()) {
                    $this->setPropertiesForRecheck($this->getPropertiesDataFromInputScheduledAction());
                }

                $scheduledAction->addData(array(
                    'listing_product_id' => $this->input->getListingProduct()->getId(),
                    'component'          => Ess_M2ePro_Helper_Component_Walmart::NICK,
                    'action_type'        => Ess_M2ePro_Model_Listing_Product::ACTION_STOP,
                    'additional_data'    => Mage::helper('M2ePro')->jsonEncode(array(
                        'params'       => $params,
                    )),
                ));

                if ($scheduledAction->getId()) {
                    $this->getScheduledActionManager()->updateAction($scheduledAction);
                } else {
                    $this->getScheduledActionManager()->addAction($scheduledAction);
                }
            }
        }

        if ($scheduledAction->isActionTypeStop()) {
            if ($this->input->hasInstructionWithTypes($this->getReviseInstructionTypes())) {
                $this->setPropertiesForRecheck($this->getPropertiesDataFromInputInstructions());
            }

            return;
        }

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product $walmartListingProduct */
        $walmartListingProduct = $this->input->getListingProduct()->getChildObject();

        $configurator = Mage::getModel('M2ePro/Walmart_Listing_Product_Action_Configurator');
        $configurator->disableAll();

        $tags = array();

        if ($scheduledAction->isActionTypeRevise()) {
            if ($scheduledAction->isForce()) {
                return;
            }

            $additionalData = $scheduledAction->getAdditionalData();

            if (isset($additionalData['configurator'])) {
                $configurator->setData($additionalData['configurator']);
            } else {
                $configurator->enableAll();
            }

            $tags = explode('/', $scheduledAction->getTag());
        }

        $tags = array_flip($tags);

        if ($this->input->hasInstructionWithTypes($this->getReviseQtyInstructionTypes())) {
            if ($this->isMeetReviseQtyRequirements()) {
                $configurator->allowQty();
                $tags['qty'] = true;
            } else {
                $configurator->disallowQty();
                unset($tags['qty']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePriceInstructionTypes())) {
            if ($this->isMeetRevisePriceRequirements()) {
                $configurator->allowPrice();
                $tags['price'] = true;
            } else {
                $configurator->disallowPrice();
                unset($tags['price']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePromotionsInstructionTypes())) {
            if ($this->isMeetRevisePromotionsRequirements()) {
                $configurator->allowPromotions();
                $tags['promotions'] = true;
            } else {
                $configurator->disallowPromotions();
                unset($tags['promotions']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseDetailsInstructionTypes())) {
            if ($this->isMeetReviseDetailsRequirements()) {
                !$walmartListingProduct->isDetailsDataChanged() &&
                $walmartListingProduct->setData('is_details_data_changed', true)->save();
            } else {
                $walmartListingProduct->isDetailsDataChanged() &&
                $walmartListingProduct->setData('is_details_data_changed', false)->save();
            }
        }

        $this->checkUpdatePriceOrPromotionsFeedsLock(
            $configurator, $tags, Ess_M2ePro_Model_Listing_Log::ACTION_REVISE_PRODUCT_ON_COMPONENT
        );

        if (count($configurator->getAllowedDataTypes()) == 0) {
            if ($scheduledAction->getId()) {
                $this->getScheduledActionManager()->deleteAction($scheduledAction);
            }

            return;
        }

        $tags = array_keys($tags);

        $scheduledAction->addData(array(
            'listing_product_id' => $this->input->getListingProduct()->getId(),
            'component'          => Ess_M2ePro_Helper_Component_Walmart::NICK,
            'action_type'        => Ess_M2ePro_Model_Listing_Product::ACTION_REVISE,
            'tag'                => '/'.implode('/', $tags).'/',
            'additional_data'    => Mage::helper('M2ePro')->jsonEncode(array(
                'params'       => $params,
                'configurator' => $configurator->getData()
            )),
        ));

        if ($scheduledAction->getId()) {
            $this->getScheduledActionManager()->updateAction($scheduledAction);
        } else {
            $this->getScheduledActionManager()->addAction($scheduledAction);
        }
    }

    //########################################

    private function isMeetStopRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();
        $variationResource = Mage::getResourceModel('M2ePro/Listing_Product_Variation');

        if (!$walmartSynchronizationTemplate->isStopMode()) {
            return false;
        }

        if (!$walmartListingProduct->isExistCategoryTemplate()) {
            return false;
        }

        if ($walmartSynchronizationTemplate->isStopStatusDisabled()) {

            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                return true;
            } else if ($variationManager->isPhysicalUnit() &&
                $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllStatusesDisabled(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
                    return true;
                }
            }
        }

        if ($walmartSynchronizationTemplate->isStopOutOfStock()) {

            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                return true;
            } else if ($variationManager->isPhysicalUnit() &&
                $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
                    return true;
                }
            }
        }

        if ($walmartSynchronizationTemplate->isStopWhenQtyMagentoHasValue()) {

            $productQty = (int)$walmartListingProduct->getQty(true);

            $typeQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyMagentoHasValueType();
            $minQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyMagentoHasValueMin();
            $maxQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyMagentoHasValueMax();

            if ($typeQty == Ess_M2ePro_Model_Walmart_Template_Synchronization::STOP_QTY_LESS &&
                $productQty <= $minQty) {

                return true;
            }

            if ($typeQty == Ess_M2ePro_Model_Walmart_Template_Synchronization::STOP_QTY_MORE &&
                $productQty >= $minQty) {

                return true;
            }

            if ($typeQty == Ess_M2ePro_Model_Walmart_Template_Synchronization::STOP_QTY_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {

                return true;
            }
        }

        if ($walmartSynchronizationTemplate->isStopWhenQtyCalculatedHasValue()) {

            $productQty = (int)$walmartListingProduct->getQty(false);

            $typeQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyCalculatedHasValueType();
            $minQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyCalculatedHasValueMin();
            $maxQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyCalculatedHasValueMax();

            if ($typeQty == Ess_M2ePro_Model_Walmart_Template_Synchronization::STOP_QTY_LESS &&
                $productQty <= $minQty) {

                return true;
            }

            if ($typeQty == Ess_M2ePro_Model_Walmart_Template_Synchronization::STOP_QTY_MORE &&
                $productQty >= $minQty) {

                return true;
            }

            if ($typeQty == Ess_M2ePro_Model_Walmart_Template_Synchronization::STOP_QTY_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {

                return true;
            }
        }

        return false;
    }

    // ---------------------------------------

    private function isMeetReviseQtyRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

        if (!$walmartSynchronizationTemplate->isReviseUpdateQty()) {
            return false;
        }

        $currentLagTime = $walmartListingProduct->getSellingFormatTemplateSource()->getLagTime();
        $onlineLagTime  = $walmartListingProduct->getOnlineLagTime();

        if ($currentLagTime != $onlineLagTime) {
            return true;
        }

        $isMaxAppliedValueModeOn = $walmartSynchronizationTemplate->isReviseUpdateQtyMaxAppliedValueModeOn();
        $maxAppliedValue = $walmartSynchronizationTemplate->getReviseUpdateQtyMaxAppliedValue();

        $productQty = $walmartListingProduct->getQty();
        $channelQty = $walmartListingProduct->getOnlineQty();

        if ($isMaxAppliedValueModeOn && $productQty > $maxAppliedValue && $channelQty > $maxAppliedValue) {
            return false;
        }

        if ($productQty != $channelQty) {
            return true;
        }

        return false;
    }

    // ---------------------------------------

    private function isMeetRevisePriceRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

        if (!$walmartSynchronizationTemplate->isReviseUpdatePrice()) {
            return false;
        }

        $currentPrice = $walmartListingProduct->getPrice();
        $onlinePrice  = $walmartListingProduct->getOnlinePrice();

        $isChanged = $walmartSynchronizationTemplate->isPriceChangedOverAllowedDeviation($onlinePrice, $currentPrice);
        if ($isChanged) {
            return true;
        }

        return false;
    }

    // ---------------------------------------

    private function isMeetRevisePromotionsRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

        if (!$walmartSynchronizationTemplate->isReviseUpdatePromotions()) {
            return false;
        }

        $promotionsActionDataBuilder = Mage::getModel('M2ePro/Walmart_Listing_Product_Action_DataBuilder_Promotions');
        $promotionsActionDataBuilder->setListingProduct($listingProduct);

        return $promotionsActionDataBuilder->getData() != $walmartListingProduct->getOnlinePromotions();
    }

    // ---------------------------------------

    private function isMeetReviseDetailsRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        $detailsActionDataBuilder = Mage::getModel('M2ePro/Walmart_Listing_Product_Action_DataBuilder_Details');
        $detailsActionDataBuilder->setListingProduct($listingProduct);

        $currentDetailsData = $detailsActionDataBuilder->getData();

        $currentStartDate = $currentDetailsData['start_date'];
        unset($currentDetailsData['start_date']);

        $currentEndDate = $currentDetailsData['end_date'];
        unset($currentDetailsData['end_date']);

        if ($currentDetailsData != $walmartListingProduct->getOnlineDetailsData()) {
            return true;
        }

        if ($currentStartDate != $walmartListingProduct->getOnlineStartDate()) {
            return true;
        }

        if ($currentEndDate != $walmartListingProduct->getOnlineEndDate()) {
            return true;
        }

        return false;
    }

    //########################################
}