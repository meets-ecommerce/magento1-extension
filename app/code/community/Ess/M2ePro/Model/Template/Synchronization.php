<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method Ess_M2ePro_Model_Amazon_Template_Synchronization|Ess_M2ePro_Model_Ebay_Template_Synchronization|Ess_M2ePro_Model_Walmart_Template_Synchronization getChildObject()
 */
class Ess_M2ePro_Model_Template_Synchronization extends Ess_M2ePro_Model_Component_Parent_Abstract
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Template_Synchronization');
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    //########################################

    public function save()
    {
        Mage::helper('M2ePro/Data_Cache_Permanent')->removeTagValues('template_synchronization');
        return parent::save();
    }

    public function delete()
    {
        Mage::helper('M2ePro/Data_Cache_Permanent')->removeTagValues('template_synchronization');
        return parent::delete();
    }

    //########################################
}