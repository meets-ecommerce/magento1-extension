<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_FullAmazonCategories_Installation
    extends Ess_M2ePro_Block_Adminhtml_Wizard_Installation
{
    //########################################

    protected function _beforeToHtml()
    {
        // Steps
        // ---------------------------------------
        $this->setChild(
            'step_marketplaces_synchronization',
            $this->helper('M2ePro/Module_Wizard')->createBlock(
                'installation_marketplacesSynchronization',$this->getNick()
            )
        );
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    //########################################

    protected function getHeaderTextHtml()
    {
        return 'Amazon Integration Upgrade Wizard';
    }

    protected function _toHtml()
    {
        $urls = Mage::helper('M2ePro')->jsonEncode(array(
            'marketplacesSynchronization' => $this->getUrl('*/*/marketplacesSynchronization')
        ));

        $js = <<<JS
        <script>
            M2ePro.url.add({$urls});
            WizardFullAmazonCategories = new WizardFullAmazonCategories;
        </script>
JS
;
        return parent::_toHtml()
               . $js
               . $this->getChildHtml('step_marketplaces_synchronization');
    }

    //########################################
}