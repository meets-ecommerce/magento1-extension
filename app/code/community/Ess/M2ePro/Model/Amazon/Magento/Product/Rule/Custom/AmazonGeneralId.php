<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Amazon_Magento_Product_Rule_Custom_AmazonGeneralId
    extends Ess_M2ePro_Model_Magento_Product_Rule_Custom_Abstract
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'amazon_item_id';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return Mage::helper('M2ePro')->__('ASIN/ISBN');
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getValueByProductInstance(Mage_Catalog_Model_Product $product)
    {
        return $product->getData('general_id');
    }

    //########################################
}