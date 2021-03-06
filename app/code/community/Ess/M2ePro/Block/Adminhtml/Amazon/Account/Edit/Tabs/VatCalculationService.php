<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Amazon_Account_Edit_Tabs_VatCalculationService
    extends Mage_Adminhtml_Block_Widget
{
    //########################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonAccountEditTabsVatCalculationService');
        // ---------------------------------------

        $this->setTemplate('M2ePro/amazon/account/tabs/vat_calculation_service.phtml');
    }

    //########################################
}
