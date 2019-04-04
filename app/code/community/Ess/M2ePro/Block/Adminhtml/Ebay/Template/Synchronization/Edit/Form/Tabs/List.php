<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Ebay_Template_Synchronization_Edit_Form_Tabs_List
    extends Ess_M2ePro_Block_Adminhtml_Ebay_Template_Synchronization_Edit_Form_Data
{
    //########################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayTemplateSynchronizationEditFormTabsList');
        // ---------------------------------------

        $this->setTemplate('M2ePro/ebay/template/synchronization/form/tabs/list.phtml');
    }

    //########################################

    public function getDefault()
    {
        return Mage::helper('M2ePro/View_Ebay')->isSimpleMode()
            ? Mage::getSingleton('M2ePro/Ebay_Template_Synchronization')->getListDefaultSettingsSimpleMode()
            : Mage::getSingleton('M2ePro/Ebay_Template_Synchronization')->getListDefaultSettingsAdvancedMode();
    }

    //########################################
}