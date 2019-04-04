<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method Ess_M2ePro_Model_Amazon_Listing_Product|Ess_M2ePro_Model_Ebay_Listing_Product|Ess_M2ePro_Model_Walmart_Listing_Product getChildObject()
 */
class Ess_M2ePro_Model_Listing_Product extends Ess_M2ePro_Model_Component_Parent_Abstract
{
    const ACTION_LIST    = 1;
    const ACTION_RELIST  = 2;
    const ACTION_REVISE  = 3;
    const ACTION_STOP    = 4;
    const ACTION_DELETE  = 5;

    const STATUS_NOT_LISTED = 0;
    const STATUS_SOLD = 1;
    const STATUS_LISTED = 2;
    const STATUS_STOPPED = 3;
    const STATUS_FINISHED = 4;
    const STATUS_UNKNOWN = 5;
    const STATUS_BLOCKED = 6;
    const STATUS_HIDDEN = 7;

    const STATUS_CHANGER_UNKNOWN = 0;
    const STATUS_CHANGER_SYNCH = 1;
    const STATUS_CHANGER_USER = 2;
    const STATUS_CHANGER_COMPONENT = 3;
    const STATUS_CHANGER_OBSERVER = 4;

    const MOVING_LISTING_OTHER_SOURCE_KEY = 'moved_from_listing_other_id';

    /**
     * It allows to delete an object without checking if it is isLocked()
     * @var bool
     */
    protected $_canBeForceDeleted = false;

    protected $_eventPrefix = 'm2epro_listing_product';

    //########################################

    /**
     * @var Ess_M2ePro_Model_Listing
     */
    protected $listingModel = NULL;

    /**
     * @var Ess_M2ePro_Model_Magento_Product_Cache
     */
    protected $magentoProductModel = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Listing_Product');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();

        $scheduledActions = Mage::getResourceModel('M2ePro/Listing_Product_ScheduledAction_Collection');
        $scheduledActions->addFieldToFilter('listing_product_id', $this->getId());
        foreach ($scheduledActions->getItems() as $item) {
            /**@var Ess_M2ePro_Model_Listing_Product_ScheduledAction $item */
            $item->delete();
        }

        $instructionCollection = Mage::getResourceModel('M2ePro/Listing_Product_Instruction_Collection');
        $instructionCollection->addFieldToFilter('listing_product_id', $this->getId());
        foreach ($instructionCollection->getItems() as $item) {
            /**@var Ess_M2ePro_Model_Listing_Product_Instruction $item */
            $item->delete();
        }

        return $temp;
    }

    //########################################

    /**
     * @return bool
     * @throws Ess_M2ePro_Model_Exception_Logic
     */
    public function isLocked()
    {
        if ($this->canBeForceDeleted()) {
            return false;
        }

        if ($this->isComponentModeEbay() && $this->getAccount()->getChildObject()->isModeSandbox()) {
            return false;
        }

        if (parent::isLocked()) {
            return true;
        }

        if ($this->getStatus() == self::STATUS_LISTED) {
            return true;
        }

        return false;
    }

    public function deleteInstance()
    {
        if ($this->isLocked()) {
            return false;
        }

        $variations = $this->getVariations(true);
        foreach ($variations as $variation) {
            $variation->deleteInstance();
        }

        /** @var Ess_M2ePro_Model_Listing_Product_Instruction[] $instructions */
        $instructions = $this->getRelatedSimpleItems(
            'Listing_Product_Instruction', 'listing_product_id', true
        );
        foreach ($instructions as $instruction) {
            $instruction->deleteInstance();
        }

        /** @var Ess_M2ePro_Model_Listing_Product_ScheduledAction[] $scheduledActions */
        $scheduledActions = $this->getRelatedSimpleItems(
            'Listing_Product_ScheduledAction', 'listing_product_id', true
        );
        foreach ($scheduledActions as $scheduledAction) {
            $scheduledAction->deleteInstance();
        }

        $tempLog = Mage::getModel('M2ePro/Listing_Log');
        $tempLog->setComponentMode($this->getComponentMode());
        $tempLog->addProductMessage($this->getListingId(),
                                    $this->getProductId(),
                                    $this->getId(),
                                    Ess_M2ePro_Helper_Data::INITIATOR_UNKNOWN,
                                    NULL,
                                    Ess_M2ePro_Model_Listing_Log::ACTION_DELETE_PRODUCT_FROM_LISTING,
                                    // M2ePro_TRANSLATIONS
                                    // Item was successfully Deleted
                                    'Item was successfully Deleted',
                                    Ess_M2ePro_Model_Log_Abstract::TYPE_NOTICE,
                                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

        $this->listingModel = NULL;
        $this->magentoProductModel = NULL;

        $this->deleteChildInstance();
        $this->delete();

        return true;
    }

    //########################################

    /**
     * @return Ess_M2ePro_Model_Listing
     */
    public function getListing()
    {
        if (is_null($this->listingModel)) {
            $this->listingModel = Mage::helper('M2ePro/Component')->getCachedComponentObject(
                $this->getComponentMode(),'Listing',$this->getData('listing_id')
            );
        }

        return $this->listingModel;
    }

    /**
     * @param Ess_M2ePro_Model_Listing $instance
     */
    public function setListing(Ess_M2ePro_Model_Listing $instance)
    {
         $this->listingModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return Ess_M2ePro_Model_Magento_Product_Cache
     */
    public function getMagentoProduct()
    {
        if (is_null($this->magentoProductModel)) {
            $this->magentoProductModel = Mage::getModel('M2ePro/Magento_Product_Cache');
            $this->magentoProductModel->setProductId($this->getProductId());
        }

        return $this->prepareMagentoProduct($this->magentoProductModel);
    }

    /**
     * @param Ess_M2ePro_Model_Magento_Product_Cache $instance
     */
    public function setMagentoProduct(Ess_M2ePro_Model_Magento_Product_Cache $instance)
    {
        $this->magentoProductModel = $this->prepareMagentoProduct($instance);
    }

    protected function prepareMagentoProduct(Ess_M2ePro_Model_Magento_Product_Cache $instance)
    {
        $instance->setStoreId($this->getListing()->getStoreId());
        $instance->setStatisticId($this->getId());

        if (method_exists($this->getChildObject(), 'prepareMagentoProduct')) {
            $instance = $this->getChildObject()->prepareMagentoProduct($instance);
        }

        return $instance;
    }

    //########################################

    /**
     * @return Ess_M2ePro_Model_Account
     */
    public function getAccount()
    {
        return $this->getListing()->getAccount();
    }

    /**
     * @return Ess_M2ePro_Model_Marketplace
     */
    public function getMarketplace()
    {
        return $this->getListing()->getMarketplace();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @param bool $tryToGetFromStorage
     * @return Ess_M2ePro_Model_Listing_Product_Variation[]
     */
    public function getVariations($asObjects = false, array $filters = array(), $tryToGetFromStorage = true)
    {
        $storageKey = "listing_product_{$this->getId()}_variations_" .
                      md5((string)$asObjects . Mage::helper('M2ePro')->jsonEncode($filters));

        if ($tryToGetFromStorage && ($cacheData = Mage::helper('M2ePro/Data_Cache_Session')->getValue($storageKey))) {
            return $cacheData;
        }

        $variations = $this->getRelatedComponentItems(
            'Listing_Product_Variation','listing_product_id',$asObjects,$filters
        );

        if ($asObjects) {
            foreach ($variations as $variation) {
                /** @var $variation Ess_M2ePro_Model_Listing_Product_Variation */
                $variation->setListingProduct($this);
            }
        }

        Mage::helper('M2ePro/Data_Cache_Session')->setValue($storageKey, $variations, array(
            'listing_product',
            "listing_product_{$this->getId()}",
            "listing_product_{$this->getId()}_variations"
        ));

        return $variations;
    }

    //########################################

    /**
     * @return int
     */
    public function getListingId()
    {
        return (int)$this->getData('listing_id');
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return (int)$this->getData('product_id');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getStatus()
    {
        return (int)$this->getData('status');
    }

    /**
     * @return int
     */
    public function getStatusChanger()
    {
        return (int)$this->getData('status_changer');
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws Ess_M2ePro_Model_Exception_Logic
     */
    public function getAdditionalData()
    {
        return $this->getSettings('additional_data');
    }

    //########################################

    /**
     * @return bool
     */
    public function isNotListed()
    {
        return $this->getStatus() == self::STATUS_NOT_LISTED;
    }

    /**
     * @return bool
     */
    public function isUnknown()
    {
        return $this->getStatus() == self::STATUS_UNKNOWN;
    }

    /**
     * @return bool
     */
    public function isBlocked()
    {
        return $this->getStatus() == self::STATUS_BLOCKED;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isListed()
    {
        return $this->getStatus() == self::STATUS_LISTED;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->getStatus() == self::STATUS_HIDDEN;
    }

    /**
     * @return bool
     */
    public function isSold()
    {
        return $this->getStatus() == self::STATUS_SOLD;
    }

    /**
     * @return bool
     */
    public function isStopped()
    {
        return $this->getStatus() == self::STATUS_STOPPED;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return $this->getStatus() == self::STATUS_FINISHED;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isListable()
    {
        return ($this->isNotListed() || $this->isSold() ||
                $this->isStopped() || $this->isFinished() ||
                $this->isHidden() || $this->isUnknown()) &&
                !$this->isBlocked();
    }

    /**
     * @return bool
     */
    public function isRelistable()
    {
        return ($this->isSold() || $this->isStopped() ||
                $this->isFinished() || $this->isUnknown()) &&
                !$this->isBlocked();
    }

    /**
     * @return bool
     */
    public function isRevisable()
    {
        return ($this->isListed() || $this->isHidden() || $this->isUnknown()) &&
                !$this->isBlocked();
    }

    /**
     * @return bool
     */
    public function isStoppable()
    {
        return ($this->isListed() || $this->isHidden() || $this->isUnknown()) &&
                !$this->isBlocked();
    }

    //########################################

    public function listAction(array $params = array())
    {
        return $this->getChildObject()->listAction($params);
    }

    public function relistAction(array $params = array())
    {
        return $this->getChildObject()->relistAction($params);
    }

    public function reviseAction(array $params = array())
    {
        return $this->getChildObject()->reviseAction($params);
    }

    public function stopAction(array $params = array())
    {
        return $this->getChildObject()->stopAction($params);
    }

    public function deleteAction(array $params = array())
    {
        return $this->getChildObject()->deleteAction($params);
    }

    // ---------------------------------------

    public static function getActionTitle($action)
    {
        $title = Mage::helper('M2ePro')->__('Unknown');

        switch ($action) {
            case self::ACTION_LIST:   $title = Mage::helper('M2ePro')->__('Listing'); break;
            case self::ACTION_RELIST: $title = Mage::helper('M2ePro')->__('Relisting'); break;
            case self::ACTION_REVISE: $title = Mage::helper('M2ePro')->__('Revising'); break;
            case self::ACTION_STOP:   $title = Mage::helper('M2ePro')->__('Stopping'); break;
            case self::ACTION_DELETE:   $title = Mage::helper('M2ePro')->__('Deleting'); break;
        }

        return $title;
    }

    //########################################

    public function canBeForceDeleted($value = NULL)
    {
        if (is_null($value)) {
            return $this->_canBeForceDeleted;
        }

        $this->_canBeForceDeleted = $value;
        return $this;
    }

    //########################################
}