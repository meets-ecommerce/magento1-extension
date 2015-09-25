<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Ebay_Configuration_General_Form extends Mage_Adminhtml_Block_Widget_Form
{
    // #################################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('ebayConfigurationGeneralForm');
        //------------------------------

        $this->setTemplate('M2ePro/ebay/configuration/general/form.phtml');
    }

    // #################################################

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/save'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $configModel = Mage::helper('M2ePro/Module')->getConfig();

        $this->view_ebay_mode = $configModel->getGroupValue('/view/ebay/', 'mode');

        $this->view_ebay_feedbacks_notification_mode = (bool)(int)$configModel->getGroupValue(
            '/view/ebay/feedbacks/notification/','mode'
        );

        $this->is_ebay_feedbacks_enabled = Mage::helper('M2ePro/View_Ebay')->isFeedbacksShouldBeShown();

        $this->use_last_specifics_mode = (bool)(int)$configModel->getGroupValue(
            '/view/ebay/template/category/','use_last_specifics'
        );
        $this->check_the_same_product_already_listed_mode = (bool)(int)$configModel->getGroupValue(
            '/ebay/connector/listing/','check_the_same_product_already_listed'
        );

        $this->upload_images_mode = (int)$configModel->getGroupValue(
            '/ebay/description/','upload_images_mode'
        );

        /** @var Ess_M2ePro_Helper_Component_Ebay_Motor_Compatibility $compatibilityHelper */
        $compatibilityHelper = Mage::helper('M2ePro/Component_Ebay_Motor_Compatibility');

        $resource = Mage::getSingleton('core/resource');
        $specificsDictionaryTable = $resource->getTableName('m2epro_ebay_dictionary_motor_specific');
        $ktypeDictionaryTable     = $resource->getTableName('m2epro_ebay_dictionary_motor_ktype');

        // ------------------------
        /** @var Ess_M2ePro_Model_Mysql4_Marketplace_Collection $specificsMarketplaceCollection */
        $specificsMarketplaceCollection = Mage::getModel('M2ePro/Marketplace')->getCollection();
        $specificsMarketplaceCollection->addFieldToFilter(
            'id',
            array('in' => $compatibilityHelper->getSpecificSupportedMarketplaces())
        );
        $specificsMarketplaceCollection->addFieldToFilter('status', Ess_M2ePro_Model_Marketplace::STATUS_ENABLE);
        $this->is_motors_specifics_marketplace_enabled = (bool)$specificsMarketplaceCollection->getSize();

        $ebayDictionaryRecords = (int)$resource->getConnection('core_read')
            ->select()
            ->from($specificsDictionaryTable, array(new Zend_Db_Expr('COUNT(*)')))
            ->where('is_custom = 0')
            ->query()
            ->fetchColumn();

        $customDictionaryRecords = (int)$resource->getConnection('core_read')
              ->select()
              ->from($specificsDictionaryTable, array(new Zend_Db_Expr('COUNT(*)')))
              ->where('is_custom = 1')
              ->query()
              ->fetchColumn();

        $this->motors_specifics_dictionary_ebay_count   = $ebayDictionaryRecords;
        $this->motors_specifics_dictionary_custom_count = $customDictionaryRecords;
        // ------------------------

        // ------------------------
        /** @var Ess_M2ePro_Model_Mysql4_Marketplace_Collection $ktypeMarketplaceCollection */
        $ktypeMarketplaceCollection = Mage::getModel('M2ePro/Marketplace')->getCollection();
        $ktypeMarketplaceCollection->addFieldToFilter(
            'id',
            array('in' => $compatibilityHelper->getKtypeSupportedMarketplaces())
        );
        $ktypeMarketplaceCollection->addFieldToFilter('status', Ess_M2ePro_Model_Marketplace::STATUS_ENABLE);
        $this->is_motors_ktypes_marketplace_enabled = (bool)$ktypeMarketplaceCollection->getSize();

        $ebayDictionaryRecords = (int)$resource->getConnection('core_read')
             ->select()
             ->from($ktypeDictionaryTable, array(new Zend_Db_Expr('COUNT(*)')))
             ->where('is_custom = 0')
             ->query()
             ->fetchColumn();

        $customDictionaryRecords = (int)$resource->getConnection('core_read')
             ->select()
             ->from($ktypeDictionaryTable, array(new Zend_Db_Expr('COUNT(*)')))
             ->where('is_custom = 1')
             ->query()
             ->fetchColumn();

        $this->motors_ktypes_dictionary_ebay_count   = $ebayDictionaryRecords;
        $this->motors_ktypes_dictionary_custom_count = $customDictionaryRecords;
        // ------------------------

        // ------------------------
        $attributesForPartsCompatibility = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
            ->addFieldToFilter('backend_type', array('eq' => 'text'))
            ->addFieldToFilter('frontend_input', array('eq' => 'textarea'))
            ->toArray();

        $this->attributes_for_parts_compatibility = $attributesForPartsCompatibility['items'];

        $this->motors_specifics_attribute = $configModel->getGroupValue('/ebay/motor/','motors_specifics_attribute');
        $this->motors_ktypes_attribute = $configModel->getGroupValue('/ebay/motor/','motors_ktypes_attribute');
        // ------------------------

        return parent::_beforeToHtml();
    }

    // #################################################

    public function getMultiCurrency()
    {
        $multiCurrency = array();

        $collection = Mage::getModel('M2ePro/Marketplace')->getCollection();
        $collection->addFieldToFilter('component_mode', Ess_M2ePro_Helper_Component_Ebay::NICK);
        $collection->addFieldToFilter('status',Ess_M2ePro_Model_Marketplace::STATUS_ENABLE);

        foreach ($collection as $marketplace) {
            $tempCurrency = $marketplace->getChildObject()->getCurrencies();
            if (strpos($tempCurrency, ',') !== false) {
                $multiCurrency[$marketplace->getTitle()]['currency'] = $tempCurrency;
                $multiCurrency[$marketplace->getTitle()]['code'] = $marketplace->getCode();
                $multiCurrency[$marketplace->getTitle()]['default'] = substr($tempCurrency,
                                                                             0,
                                                                             strpos($tempCurrency, ','));
            }
        }

        return $multiCurrency;

    }

    public function isCurrencyForCode($code, $currency)
    {
        return $currency == Mage::helper('M2ePro/Module')->getConfig()
                                                         ->getGroupValue('/ebay/selling/currency/', $code);
    }

    // #################################################
}