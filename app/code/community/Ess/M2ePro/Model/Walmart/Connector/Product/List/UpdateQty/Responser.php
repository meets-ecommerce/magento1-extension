<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Walmart_Connector_Product_List_UpdateQty_Responser
    extends Ess_M2ePro_Model_Walmart_Connector_Product_Responser
{
    /** @var Ess_M2ePro_Model_Walmart_Listing_Product_Action_ProcessingList */
    protected $processingList;

    // ########################################

    protected function processSuccess(array $params = array())
    {
        $this->getResponseObject()->processSuccess($params);
        $this->isSuccess = true;
    }

    protected function getSuccessfulMessage()
    {
        return NULL;
    }

    // ########################################

    protected function getResponseObject()
    {
        $responseObject = parent::getResponseObject();
        $responseObject->setRequestMetaData(array());

        return $responseObject;
    }

    // ########################################

    protected function getOrmActionType()
    {
        switch ($this->getActionType()) {
            case Ess_M2ePro_Model_Listing_Product::ACTION_LIST:
                return 'List_UpdateQty';
        }

        throw new Ess_M2ePro_Model_Exception('Wrong Action type');
    }

    // ---------------------------------------

    protected function getRequestDataObject()
    {
        $requestObject = parent::getRequestDataObject();
        $requestObject->setData($this->processingList->getRelistRequestData());

        return $requestObject;
    }

    protected function getConfigurator()
    {
        $configurator = parent::getConfigurator();
        $configurator->setData($this->processingList->getRelistConfiguratorData());

        return $configurator;
    }

    //########################################

    public function setProcessingList(Ess_M2ePro_Model_Walmart_Listing_Product_Action_ProcessingList $processingList)
    {
        $this->processingList = $processingList;
        return $this;
    }

    // ########################################
}