<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Magento_Product_Rule_Condition_Product
    extends Ess_M2ePro_Model_Magento_Product_Rule_Condition_Product
{
    //########################################

    /**
     * @return array
     */
    protected function getCustomFilters()
    {
        $ebayFilters = array(
            'ebay_status' => 'EbayStatus',
            'ebay_item_id' => 'EbayItemId',
            'ebay_available_qty' => 'EbayAvailableQty',
            'ebay_sold_qty' => 'EbaySoldQty',
            'ebay_online_current_price' => 'EbayPrice',
            'ebay_online_start_price' => 'EbayStartPrice',
            'ebay_online_reserve_price' => 'EbayReservePrice',
            'ebay_online_buyitnow_price' => 'EbayBuyItNowPrice',
            'ebay_online_title' => 'EbayTitle',
            'ebay_online_sku' => 'EbaySku',
            'ebay_online_category_id' => 'EbayCategoryId',
            'ebay_online_category_path' => 'EbayCategoryPath',
            'ebay_start_date' => 'EbayStartDate',
            'ebay_end_date' => 'EbayEndDate',
        );

        return array_merge_recursive(
            parent::getCustomFilters(),
            $ebayFilters
        );
    }

    /**
     * @param $filterId
     * @param $isReadyToCache
     * @return Ess_M2ePro_Model_Magento_Product_Rule_Custom_Abstract
     */
    protected function getCustomFilterInstance($filterId, $isReadyToCache = true)
    {
        $parentFilters = parent::getCustomFilters();
        if (isset($parentFilters[$filterId])) {
            return parent::getCustomFilterInstance($filterId, $isReadyToCache);
        }

        $customFilters = $this->getCustomFilters();
        if (!isset($customFilters[$filterId])) {
            return null;
        }

        if (isset($this->_customFiltersCache[$filterId])) {
            return $this->_customFiltersCache[$filterId];
        }

        /** @var Ess_M2ePro_Model_Magento_Product_Rule_Custom_Abstract $model */
        $model = Mage::getModel('M2ePro/Ebay_Magento_Product_Rule_Custom_'.$customFilters[$filterId]);
        $model->setFilterOperator($this->getData('operator'))
              ->setFilterCondition($this->getData('value'));

        $isReadyToCache && $this->_customFiltersCache[$filterId] = $model;
        return $model;
    }

    //########################################

    /**
     * @param mixed $validatedValue
     * @return bool
     */
    public function validateAttribute($validatedValue)
    {
        if (is_array($validatedValue)) {
            $result = false;

            foreach ($validatedValue as $value) {
                $result = parent::validateAttribute($value);
                if ($result) {
                    break;
                }
            }

            return $result;
        }

        return parent::validateAttribute($validatedValue);
    }

    //########################################
}