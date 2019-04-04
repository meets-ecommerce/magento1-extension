<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Walmart_Template_Synchronization_Edit_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    //########################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        // ---------------------------------------
        $this->setTemplate('M2ePro/walmart/template/synchronization/form.phtml');
    }

    protected function _beforeToHtml()
    {
        $this->setChild('walmart_template_synchronization_edit_form_tabs_list',
            $this->getLayout()->createBlock('M2ePro/adminhtml_walmart_template_synchronization_edit_tabs_list')
        );

        $this->setChild('walmart_template_synchronization_edit_form_tabs_revise',
            $this->getLayout()->createBlock('M2ePro/adminhtml_walmart_template_synchronization_edit_tabs_revise')
        );

        $this->setChild('walmart_template_synchronization_edit_form_tabs_relist',
            $this->getLayout()->createBlock('M2ePro/adminhtml_walmart_template_synchronization_edit_tabs_relist')
        );

        $this->setChild('walmart_template_synchronization_edit_form_tabs_stop',
            $this->getLayout()->createBlock('M2ePro/adminhtml_walmart_template_synchronization_edit_tabs_stop')
        );

        return parent::_beforeToHtml();
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/save'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}