<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Template_Description_AffectedListingsProducts
    extends Ess_M2ePro_Model_Template_AffectedListingsProducts_Abstract
{
    //########################################

    public function getObjects(array $filters = array())
    {
        $templateManager = Mage::getModel('M2ePro/Ebay_Template_Manager');
        $templateManager->setTemplate(Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_DESCRIPTION);

        $listingsProducts = $templateManager->getAffectedOwnerObjects(
            Ess_M2ePro_Model_Ebay_Template_Manager::OWNER_LISTING_PRODUCT, $this->model->getId(), false
        );

        $listings = $templateManager->getAffectedOwnerObjects(
            Ess_M2ePro_Model_Ebay_Template_Manager::OWNER_LISTING, $this->model->getId(), false
        );

        foreach ($listings as $listing) {

            $listingAffectedProducts = Mage::getModel('M2ePro/Ebay_Listing_AffectedListingsProducts');
            $listingAffectedProducts->setModel($listing);

            $tempListingsProducts = $listingAffectedProducts->getObjects(
                array('template' => Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_DESCRIPTION)
            );

            foreach ($tempListingsProducts as $listingProduct) {
                if (!isset($listingsProducts[$listingProduct['id']])) {
                    $listingsProducts[$listingProduct['id']] = $listingProduct;
                }
            }
        }

        return $listingsProducts;
    }

    public function getData($columns = '*', array $filters = array())
    {
        $templateManager = Mage::getModel('M2ePro/Ebay_Template_Manager');
        $templateManager->setTemplate(Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_DESCRIPTION);

        $listingsProducts = $templateManager->getAffectedOwnerObjects(
            Ess_M2ePro_Model_Ebay_Template_Manager::OWNER_LISTING_PRODUCT, $this->model->getId(), true, $columns
        );

        $listings = $templateManager->getAffectedOwnerObjects(
            Ess_M2ePro_Model_Ebay_Template_Manager::OWNER_LISTING, $this->model->getId(), false
        );

        foreach ($listings as $listing) {

            $listingAffectedProducts = Mage::getModel('M2ePro/Ebay_Listing_AffectedListingsProducts');
            $listingAffectedProducts->setModel($listing);

            $tempListingsProducts = $listingAffectedProducts->getData(
                $columns,
                array('template' => Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_DESCRIPTION)
            );

            foreach ($tempListingsProducts as $listingProduct) {
                if (!isset($listingsProducts[$listingProduct['id']])) {
                    $listingsProducts[$listingProduct['id']] = $listingProduct;
                }
            }
        }

        return $listingsProducts;
    }

    public function getIds(array $filters = array())
    {
        $templateManager = Mage::getModel('M2ePro/Ebay_Template_Manager');
        $templateManager->setTemplate(Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_DESCRIPTION);

        $listingsProducts = $templateManager->getAffectedOwnerObjects(
            Ess_M2ePro_Model_Ebay_Template_Manager::OWNER_LISTING_PRODUCT, $this->model->getId(), true, array('id')
        );

        $listings = $templateManager->getAffectedOwnerObjects(
            Ess_M2ePro_Model_Ebay_Template_Manager::OWNER_LISTING, $this->model->getId(), false
        );

        foreach ($listings as $listing) {

            $listingAffectedProducts = Mage::getModel('M2ePro/Ebay_Listing_AffectedListingsProducts');
            $listingAffectedProducts->setModel($listing);

            $tempListingsProducts = $listingAffectedProducts->getData(
                array('id'),
                array('template' => Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_DESCRIPTION)
            );

            foreach ($tempListingsProducts as $listingProduct) {
                if (!isset($listingsProducts[$listingProduct['id']])) {
                    $listingsProducts[$listingProduct['id']] = $listingProduct;
                }
            }
        }

        return array_keys($listingsProducts);
    }

    //########################################
}