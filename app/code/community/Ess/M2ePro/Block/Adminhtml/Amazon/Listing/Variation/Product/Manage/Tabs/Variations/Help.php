<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Amazon_Listing_Variation_Product_Manage_Tabs_Variations_Help
    extends Mage_Adminhtml_Block_Widget
{
    //########################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingViewHelp');
        // ---------------------------------------

        $this->setTemplate('M2ePro/amazon/listing/variation/product/manage/tabs/variations/help.phtml');
    }

    //########################################
}