<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Walmart_Connector_Product_ProcessingRunner
    extends Ess_M2ePro_Model_Connector_Command_Pending_Processing_Single_Runner
{
    /** @var Ess_M2ePro_Model_Listing_Product $listingProduct */
    private $listingProduct = NULL;

    /** @var Ess_M2ePro_Model_Walmart_Listing_Product_Action_Processing $processingAction */
    private $processingAction = NULL;

    // ########################################

    public function setListingProduct(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    public function setProcessingAction(Ess_M2ePro_Model_Walmart_Listing_Product_Action_Processing $processingAction)
    {
        $this->processingAction = $processingAction;
        return $this;
    }

    // ########################################

    public function processSuccess()
    {
        // listing product can be removed during processing action
        if (is_null($this->getListingProduct()->getId())) {
            return true;
        }

        return parent::processSuccess();
    }

    public function processExpired()
    {
        // listing product can be removed during processing action
        if (is_null($this->getListingProduct()->getId())) {
            return;
        }

        if ($this->getProcessingAction() && !$this->getProcessingAction()->isPrepared()) {
            return;
        }

        $this->getResponser()->failDetected($this->getExpiredErrorMessage());
    }

    public function complete()
    {
        // listing product can be removed during processing action
        if (is_null($this->getListingProduct()->getId())) {
            $this->getProcessingObject()->deleteInstance();
            return;
        }

        if ($this->getProcessingAction() && !$this->getProcessingAction()->isPrepared()) {
            $this->stop();
            return;
        }

        parent::complete();
    }

    // ########################################

    public function prepare()
    {
        if (is_null($this->getProcessingObject()) || !$this->getProcessingObject()->getId()) {
            throw new Ess_M2ePro_Model_Exception_Logic('Processing does not exist.');
        }

        if (is_null($this->getProcessingAction()) || !$this->getProcessingAction()->getId()) {
            throw new Ess_M2ePro_Model_Exception_Logic('Processing Action does not exist.');
        }

        $params = $this->getParams();

        $this->getProcessingObject()->setSettings('params', $this->getParams())->save();

        $this->getProcessingAction()->setData('is_prepared', 1);
        $this->getProcessingAction()->setData(
            'request_data', Mage::helper('M2ePro')->jsonEncode($params['request_data'])
        );
        $this->getProcessingAction()->save();
    }

    public function stop()
    {
        if (is_null($this->getProcessingObject()) || !$this->getProcessingObject()->getId()) {
            return;
        }

        if (is_null($this->getProcessingAction()) || !$this->getProcessingAction()->getId()) {
            return;
        }

        $this->getProcessingAction()->deleteInstance();
        $this->getProcessingObject()->deleteInstance();

        $this->unsetLocks();
    }

    // ########################################

    protected function eventBefore()
    {
        $params = $this->getParams();

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product_Action_Processing $processingAction */
        $processingAction = Mage::getModel('M2ePro/Walmart_Listing_Product_Action_Processing');
        $processingAction->setData(array(
            'listing_product_id' => $params['listing_product_id'],
            'processing_id'      => $this->getProcessingObject()->getId(),
            'type'               => $this->getProcessingActionType(),
            'is_prepared'        => 0,
            'group_hash'         => $params['group_hash'],
        ));
        $processingAction->save();
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        $this->getListingProduct()->addProcessingLock(NULL, $this->getProcessingObject()->getId());
        $this->getListingProduct()->addProcessingLock('in_action', $this->getProcessingObject()->getId());
        $this->getListingProduct()->addProcessingLock(
            $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
        );

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product $walmartListingProduct */
        $walmartListingProduct = $this->getListingProduct()->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

        if ($variationManager->isRelationChildType()) {
            /** @var Ess_M2ePro_Model_Listing_Product $parentListingProduct */
            $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();

            $parentListingProduct->addProcessingLock(NULL, $this->getProcessingObject()->getId());
            $parentListingProduct->addProcessingLock(
                'child_products_in_action', $this->getProcessingObject()->getId()
            );
        }

        $this->getListingProduct()->getListing()->addProcessingLock(NULL, $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        $this->getListingProduct()->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
        $this->getListingProduct()->deleteProcessingLocks('in_action', $this->getProcessingObject()->getId());
        $this->getListingProduct()->deleteProcessingLocks(
            $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
        );

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product $walmartListingProduct */
        $walmartListingProduct = $this->getListingProduct()->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

        if ($variationManager->isRelationChildType()) {
            /** @var Ess_M2ePro_Model_Listing_Product $parentListingProduct */
            $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();

            $parentListingProduct->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
            $parentListingProduct->deleteProcessingLocks(
                'child_products_in_action', $this->getProcessingObject()->getId()
            );
        }

        $this->getListingProduct()->getListing()->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
    }

    // ########################################

    protected function getProcessingActionType()
    {
        $params = $this->getParams();

        switch ($params['action_type']) {
            case Ess_M2ePro_Model_Listing_Product::ACTION_LIST:
                return Ess_M2ePro_Model_Walmart_Listing_Product_Action_Processing::TYPE_ADD;

            case Ess_M2ePro_Model_Listing_Product::ACTION_RELIST:
            case Ess_M2ePro_Model_Listing_Product::ACTION_REVISE:
            case Ess_M2ePro_Model_Listing_Product::ACTION_STOP:
                return Ess_M2ePro_Model_Walmart_Listing_Product_Action_Processing::TYPE_UPDATE;

            default:
                throw new Ess_M2ePro_Model_Exception_Logic('Unknown action type.');
        }
    }

    protected function getListingProduct()
    {
        if (!is_null($this->listingProduct)) {
            return $this->listingProduct;
        }

        $params = $this->getParams();

        /** @var Ess_M2ePro_Model_Mysql4_Listing_Product_Collection $collection */
        $collection = Mage::helper('M2ePro/Component_Walmart')->getCollection('Listing_Product');
        $collection->addFieldToFilter('id', array('in' => $params['listing_product_id']));

        return $this->listingProduct = $collection->getFirstItem();
    }

    protected function getProcessingAction()
    {
        if (!is_null($this->processingAction)) {
            return $this->processingAction;
        }

        $processingActionCollection = Mage::getResourceModel(
            'M2ePro/Walmart_Listing_Product_Action_Processing_Collection'
        );
        $processingActionCollection->addFieldToFilter('processing_id', $this->getProcessingObject()->getId());

        $processingAction = $processingActionCollection->getFirstItem();

        return $processingAction->getId() ? $this->processingAction = $processingAction : NULL;
    }

    // ########################################
}