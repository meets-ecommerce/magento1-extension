<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Indexer_Listing_Product_Parent_Manager extends Ess_M2ePro_Model_Abstract
{
    /** @var Ess_M2ePro_Model_Listing */
    private $listing;

    const INDEXER_LIFETIME = 1800;

    //########################################

    public function __construct()
    {
        parent::__construct();

        $listing = null;
        $args = func_get_args();
        !empty($args[0][0]) && $listing = $args[0][0];

        $this->listing = $listing;
    }

    //########################################

    public function prepare()
    {
        if ($this->isUpToDate()) {
            return;
        }

        $resourceModel = Mage::getResourceModel(
            'M2ePro/'.ucfirst($this->listing->getComponentMode()).'_Indexer_Listing_Product_Parent'
        );
        $resourceModel->clear($this->listing->getId());
        $resourceModel->build($this->listing);

        $this->markAsIsUpToDate();
    }

    public function markInvalidated()
    {
        Mage::helper('M2ePro/Data_Cache_Permanent')->removeValue(
            $this->getUpToDateCacheKey()
        );
        return $this;
    }

    //########################################

    private function isUpToDate()
    {
        return Mage::helper('M2ePro/Data_Cache_Permanent')->getValue(
            $this->getUpToDateCacheKey()
        );
    }

    private function markAsIsUpToDate()
    {
        Mage::helper('M2ePro/Data_Cache_Permanent')->setValue(
            $this->getUpToDateCacheKey(),
            'true',
            array('indexer_listing_product_parent'),
            self::INDEXER_LIFETIME
        );
        return $this;
    }

    private function getUpToDateCacheKey()
    {
        return '_indexer_listing_product_parent_up_to_date_for_listing_id_' . $this->listing->getId();
    }

    //########################################
}