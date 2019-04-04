<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

abstract class Ess_M2ePro_Model_Walmart_Listing_Product_Action_Type_Request
    extends Ess_M2ePro_Model_Walmart_Listing_Product_Action_Request
{
    /**
     * @var array
     */
    protected $cachedData = array();

    /**
     * @var array
     */
    private $dataTypes = array(
        'qty',
        'price',
        'promotions',
        'details',
    );

    /**
     * @var Ess_M2ePro_Model_Walmart_Listing_Product_Action_DataBuilder_Abstract[]
     */
    private $dataBuilders = array();

    //########################################

    public function setCachedData(array $data)
    {
        $this->cachedData = $data;
    }

    /**
     * @return array
     */
    public function getCachedData()
    {
        return $this->cachedData;
    }

    //########################################

    /**
     * @return array
     */
    public function getData()
    {
        $this->beforeBuildDataEvent();
        $data = $this->getActionData();

        $data = $this->prepareFinalData($data);
        $this->collectDataBuildersWarningMessages();

        return $data;
    }

    //########################################

    protected function beforeBuildDataEvent() {}

    abstract protected function getActionData();

    // ---------------------------------------

    protected function prepareFinalData(array $data)
    {
        return $data;
    }

    protected function collectDataBuildersWarningMessages()
    {
        foreach ($this->dataTypes as $requestType) {

            $messages = $this->getDataBuilder($requestType)->getWarningMessages();

            foreach ($messages as $message) {
                $this->addWarningMessage($message);
            }
        }
    }

    //########################################

    /**
     * @return array
     */
    public function getQtyData()
    {
        if (!$this->getConfigurator()->isQtyAllowed()) {
            return array();
        }

        $dataBuilder = $this->getDataBuilder('qty');
        return $dataBuilder->getData();
    }

    /**
     * @return array
     */
    public function getPriceData()
    {
        if (!$this->getConfigurator()->isPriceAllowed()) {
            return array();
        }

        $dataBuilder = $this->getDataBuilder('price');
        return $dataBuilder->getData();
    }

    /**
     * @return array
     */
    public function getPromotionsData()
    {
        if (!$this->getConfigurator()->isPromotionsAllowed()) {
            return array();
        }

        $dataBuilder = $this->getDataBuilder('promotions');
        $data = $dataBuilder->getData();

        $this->addMetaData('promotions_data', $data);

        return $data;
    }

    /**
     * @return array
     */
    public function getDetailsData()
    {
        if (!$this->getConfigurator()->isDetailsAllowed()) {
            return array();
        }

        $dataBuilder = $this->getDataBuilder('details');
        $data = $dataBuilder->getData();

        $this->addMetaData('details_data', $data);

        return $data;
    }

    //########################################

    /**
     * @param $type
     * @return Ess_M2ePro_Model_Walmart_Listing_Product_Action_DataBuilder_Abstract
     */
    private function getDataBuilder($type)
    {
        if (!isset($this->dataBuilders[$type])) {

            /** @var Ess_M2ePro_Model_Walmart_Listing_Product_Action_DataBuilder_Abstract $dataBuilder */
            $dataBuilder = Mage::getModel('M2ePro/Walmart_Listing_Product_Action_DataBuilder_'.ucfirst($type));

            $dataBuilder->setParams($this->getParams());
            $dataBuilder->setListingProduct($this->getListingProduct());
            $dataBuilder->setCachedData($this->getCachedData());

            $this->dataBuilders[$type] = $dataBuilder;
        }

        return $this->dataBuilders[$type];
    }

    //########################################
}