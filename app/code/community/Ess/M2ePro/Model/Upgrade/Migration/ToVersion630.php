<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Upgrade_Migration_ToVersion630
{
    const BACKUP_TABLE_PREFIX = '__backup_v630';

    /** @var Ess_M2ePro_Model_Upgrade_MySqlSetup */
    private $installer = NULL;

    private $forceAllSteps = false;

    //########################################

    /**
     * @return Ess_M2ePro_Model_Upgrade_MySqlSetup
     */
    public function getInstaller()
    {
        return $this->installer;
    }

    /**
     * @param Ess_M2ePro_Model_Upgrade_MySqlSetup $installer
     */
    public function setInstaller(Ess_M2ePro_Model_Upgrade_MySqlSetup $installer)
    {
        $this->installer = $installer;
    }

    // ---------------------------------------

    public function setForceAllSteps($value = true)
    {
        $this->forceAllSteps = $value;
    }

    //########################################

    public function migrate()
    {
        $this->processGeneral();
        $this->processMarketplace();
        $this->processDescriptionTemplate();
        $this->processListingProduct();
        $this->processAutoActions();
        $this->processProcessing();
        $this->processListing();
    }

    //########################################

    private function processGeneral()
    {
        /** @var Ess_M2ePro_Model_Upgrade_Migration_ToVersion630_General $model */
        $model = Mage::getModel('M2ePro/Upgrade_Migration_ToVersion630_General');
        $model->setInstaller($this->installer);
        $model->setForceAllSteps($this->forceAllSteps);
        $model->process();
    }

    private function processMarketplace()
    {
        /** @var Ess_M2ePro_Model_Upgrade_Migration_ToVersion630_Marketplace $model */
        $model = Mage::getModel('M2ePro/Upgrade_Migration_ToVersion630_Marketplace');
        $model->setInstaller($this->installer);
        $model->setForceAllSteps($this->forceAllSteps);
        $model->process();
    }

    private function processDescriptionTemplate()
    {
        /** @var Ess_M2ePro_Model_Upgrade_Migration_ToVersion630_DescriptionTemplate $model */
        $model = Mage::getModel('M2ePro/Upgrade_Migration_ToVersion630_DescriptionTemplate');
        $model->setInstaller($this->installer);
        $model->setForceAllSteps($this->forceAllSteps);
        $model->process();
    }

    private function processListingProduct()
    {
        /** @var Ess_M2ePro_Model_Upgrade_Migration_ToVersion630_ListingProduct $model */
        $model = Mage::getModel('M2ePro/Upgrade_Migration_ToVersion630_ListingProduct');
        $model->setInstaller($this->installer);
        $model->setForceAllSteps($this->forceAllSteps);
        $model->process();
    }

    private function processAutoActions()
    {
        /** @var Ess_M2ePro_Model_Upgrade_Migration_ToVersion630_AutoAction $model */
        $model = Mage::getModel('M2ePro/Upgrade_Migration_ToVersion630_AutoAction');
        $model->setInstaller($this->installer);
        $model->setForceAllSteps($this->forceAllSteps);
        $model->process();
    }

    private function processProcessing()
    {
        /** @var Ess_M2ePro_Model_Upgrade_Migration_ToVersion630_Processing $model */
        $model = Mage::getModel('M2ePro/Upgrade_Migration_ToVersion630_Processing');
        $model->setInstaller($this->installer);
        $model->process();
    }

    private function processListing()
    {
        /** @var Ess_M2ePro_Model_Upgrade_Migration_ToVersion630_Listing $model */
        $model = Mage::getModel('M2ePro/Upgrade_Migration_ToVersion630_Listing');
        $model->setInstaller($this->installer);
        $model->process();
    }

    //########################################
}