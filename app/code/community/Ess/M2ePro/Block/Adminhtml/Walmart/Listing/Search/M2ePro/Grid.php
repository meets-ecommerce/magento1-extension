<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

use Ess_M2ePro_Model_Walmart_Listing_Product_Action_List_Processor as ListProcessor;

class Ess_M2ePro_Block_Adminhtml_Walmart_Listing_Search_M2ePro_Grid
    extends Ess_M2ePro_Block_Adminhtml_Walmart_Listing_Search_Grid
{
    private $lockedDataCache = array();

    //########################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingSearchM2eProGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort(false);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        /* @var $collection Ess_M2ePro_Model_Mysql4_Magento_Product_Collection */
        $collection = Mage::getConfig()->getModelInstance('Ess_M2ePro_Model_Mysql4_Magento_Product_Collection',
                                                           Mage::getModel('catalog/product')->getResource());

        $collection->getSelect()->distinct();
        $collection->setListingProductModeOn();

        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');

        $collection->setStoreId(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
        $collection->joinStockItem(array(
            'is_in_stock' => 'is_in_stock'
        ));

        $collection->joinTable(
            array('lp' => 'M2ePro/Listing_Product'),
            'product_id=entity_id',
            array(
                'id'              => 'id',
                'status'          => 'status',
                'component_mode'  => 'component_mode',
                'listing_id'      => 'listing_id',
                'additional_data' => 'additional_data',
            )
        );
        $collection->joinTable(
            array('wlp' => 'M2ePro/Walmart_Listing_Product'),
            'listing_product_id=id',
            array(
                'listing_product_id'           => 'listing_product_id',
                'listing_other_id'             => new Zend_Db_Expr('NULL'),
                'variation_parent_id'          => 'variation_parent_id',
                'is_variation_parent'          => 'is_variation_parent',
                'variation_child_statuses'     => 'variation_child_statuses',
                'online_sku'                   => 'sku',
                'gtin'                         => 'gtin',
                'upc'                          => 'upc',
                'ean'                          => 'ean',
                'isbn'                         => 'isbn',
                'wpid'                         => 'wpid',
                'channel_url'                  => 'channel_url',
                'item_id'                      => 'item_id',
                'online_qty'                   => 'online_qty',
                'online_price'                 => 'online_price',
                'is_online_price_invalid'      => 'is_online_price_invalid',
            ),
            'variation_parent_id IS NULL'
        );
        $collection->joinTable(
            array('l' => 'M2ePro/Listing'),
            'id=listing_id',
            array(
                'store_id'       => 'store_id',
                'account_id'     => 'account_id',
                'marketplace_id' => 'marketplace_id',
                'listing_title'  => 'title',
            )
        );

        $accountId = (int)$this->getRequest()->getParam('walmartAccount', false);
        $marketplaceId = (int)$this->getRequest()->getParam('walmartMarketplace', false);

        if ($accountId) {
            $collection->getSelect()->where('l.account_id = ?', $accountId);
        }

        if ($marketplaceId) {
            $collection->getSelect()->where('l.marketplace_id = ?', $marketplaceId);
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    //########################################

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getData('name');
        $title = Mage::helper('M2ePro')->escapeHtml($title);

        $listingWord  = Mage::helper('M2ePro')->__('Listing');
        $listingTitle = Mage::helper('M2ePro')->escapeHtml($row->getData('listing_title'));
        strlen($listingTitle) > 50 && $listingTitle = substr($listingTitle, 0, 50) . '...';

        $listingUrl = $this->getUrl('*/adminhtml_walmart_listing/view',
                                    array('id' => $row->getData('listing_id')));

        $value = <<<HTML
<span>{$title}</span>
<br/><hr style="border:none; border-top:1px solid silver; margin: 2px 0px;"/>
<strong>{$listingWord}:</strong>&nbsp;
<a href="{$listingUrl}" target="_blank">{$listingTitle}</a>
HTML;

        $sku     = Mage::helper('M2ePro')->escapeHtml($row->getData('sku'));
        $skuWord = Mage::helper('M2ePro')->__('SKU');

        $value .= <<<HTML
<br/><strong>{$skuWord}:</strong>&nbsp;
{$sku}
HTML;

        /** @var Ess_M2ePro_Model_Listing_Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = Mage::helper('M2ePro/Component_Walmart')->getObject('Listing_Product', $listingProductId);

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product_Variation_Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if ($variationManager->isVariationParent()) {

            $productAttributes = $variationManager->getTypeModel()->getProductAttributes();

            $virtualProductAttributes = $variationManager->getTypeModel()->getVirtualProductAttributes();
            $virtualChannelAttributes = $variationManager->getTypeModel()->getVirtualChannelAttributes();

            $attributesStr = '';

            if (empty($virtualProductAttributes) && empty($virtualChannelAttributes)) {
                $attributesStr = implode(', ', $productAttributes);
            } else {
                foreach ($productAttributes as $attribute) {
                    if (in_array($attribute, array_keys($virtualProductAttributes))) {

                        $attributesStr .= '<span style="border-bottom: 2px dotted grey">' . $attribute .
                            ' (' . $virtualProductAttributes[$attribute] . ')</span>, ';

                    } else if (in_array($attribute, array_keys($virtualChannelAttributes))) {

                        $attributesStr .= '<span>' . $attribute .
                            ' (' . $virtualChannelAttributes[$attribute] . ')</span>, ';

                    } else {
                        $attributesStr .= $attribute . ', ';
                    }
                }
                $attributesStr = rtrim($attributesStr, ', ');
            }

            $value .= <<<HTML
<div style="font-size: 11px; font-weight: bold; color: grey;">
    {$attributesStr}
</div>
HTML;
        }

        if ($variationManager->isIndividualType() &&
            $variationManager->getTypeModel()->isVariationProductMatched()
        ) {

            $optionsStr = '';
            $productOptions = $variationManager->getTypeModel()->getProductOptions();

            foreach ($productOptions as $attribute => $option) {

                $attribute = Mage::helper('M2ePro')->escapeHtml($attribute);
                !$option && $option = '--';
                $option = Mage::helper('M2ePro')->escapeHtml($option);

                $optionsStr .= <<<HTML
<strong>{$attribute}</strong>:&nbsp;{$option}<br/>
HTML;
            }

            $value .= <<<HTML
<br/>
<div style="font-size: 11px; color: grey;">
    {$optionsStr}
</div>
<br/>
HTML;
        }

        return $value;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $value = $this->getProductStatus($row, $row->getData('status'));

        /** @var Ess_M2ePro_Model_Listing_Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = Mage::helper('M2ePro/Component_Walmart')->getObject('Listing_Product', $listingProductId);

        /** @var Ess_M2ePro_Model_Walmart_Listing_Product_Variation_Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if (!$variationManager->isVariationParent()) {
            $statusChangeReasons = $listingProduct->getChildObject()->getStatusChangeReasons();
            return $value . $this->getStatusChangeReasons($statusChangeReasons) .
                            $this->getScheduledTag($row) .
                            $this->getLockedTag($row);
        }

        $html = '';

        $sNotListed = Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED;
        $sListed    = Ess_M2ePro_Model_Listing_Product::STATUS_LISTED;
        $sStopped   = Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED;
        $sBlocked   = Ess_M2ePro_Model_Listing_Product::STATUS_BLOCKED;

        $variationsStatuses = $row->getData('variation_child_statuses');

        if (empty($variationsStatuses)) {

            return $this->getProductStatus($row, $sNotListed) .
                   $this->getScheduledTag($row) .
                   $this->getLockedTag($row);
        }

        $sortedStatuses     = array();
        $variationsStatuses = Mage::helper('M2ePro')->jsonDecode($variationsStatuses);

        isset($variationsStatuses[$sNotListed]) && $sortedStatuses[$sNotListed] = $variationsStatuses[$sNotListed];
        isset($variationsStatuses[$sListed])    && $sortedStatuses[$sListed]    = $variationsStatuses[$sListed];
        isset($variationsStatuses[$sStopped])   && $sortedStatuses[$sStopped]   = $variationsStatuses[$sStopped];
        isset($variationsStatuses[$sBlocked])   && $sortedStatuses[$sBlocked]   = $variationsStatuses[$sBlocked];

        foreach ($sortedStatuses as $status => $productsCount) {

            if (empty($productsCount)) {
                continue;
            }

            $productsCount = '['.$productsCount.']';
            $html .= $this->getProductStatus($row, $status) . '&nbsp;'. $productsCount . '<br/>';
        }

        return $html . $this->getScheduledTag($row) .
                       $this->getLockedTag($row);
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $altTitle = Mage::helper('M2ePro')->escapeHtml(Mage::helper('M2ePro')->__('Go to Listing'));
        $iconSrc  = $this->getSkinUrl('M2ePro/images/goto_listing.png');

        $manageUrl = $this->getUrl('*/adminhtml_walmart_listing/view/',array(
            'id' => $row->getData('listing_id'),
            'filter' => base64_encode(
                'product_id[from]='.(int)$row->getData('entity_id')
                .'&product_id[to]='.(int)$row->getData('entity_id')
            )
        ));

        $html = <<<HTML
<div style="float:right; margin:5px 15px 0 0;">
    <a title="{$altTitle}" target="_blank" href="{$manageUrl}"><img src="{$iconSrc}" alt="{$altTitle}" /></a>
</div>
HTML;

        return $html;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ((!$row->getData('is_variation_parent') &&
            $row->getData('status') == Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED)) {

            return '<span style="color: gray;">' . Mage::helper('M2ePro')->__('Not Listed') . '</span>';
        }

        $currentOnlinePrice = (float)$row->getData('online_price');

        if (empty($currentOnlinePrice) ||
            ($row->getData('status') == Ess_M2ePro_Model_Listing_Product::STATUS_BLOCKED &&
             !$row->getData('is_online_price_invalid')))
        {
            return Mage::helper('M2ePro')->__('N/A');
        }

        $marketplaceId = $row->getData('marketplace_id');
        $currency = Mage::helper('M2ePro/Component_Walmart')
            ->getCachedObject('Marketplace', $marketplaceId)
            ->getChildObject()
            ->getDefaultCurrency();

        if ($row->getData('is_variation_parent')) {

            $iconHelpPath    = $this->getSkinUrl('M2ePro/images/i_logo.png');
            $toolTipIconPath = $this->getSkinUrl('M2ePro/images/i_icon.png');

            $noticeText = Mage::helper('M2ePro')->__('The value is calculated as minimum price of all Child Products.');

            $priceHtml = <<<HTML
<img class="tool-tip-image" style="vertical-align: middle;" src="{$toolTipIconPath}"
    >&nbsp;<span class="tool-tip-message" style="display:none; text-align: left; width: 110px; background: #E3E3E3;">
    <img src="{$iconHelpPath}">
    <span style="color:gray;">
        {$noticeText}
    </span>
</span>
HTML;

            if (!empty($currentOnlinePrice)) {
                $currentOnlinePrice = Mage::app()->getLocale()->currency($currency)->toCurrency($currentOnlinePrice);
                $priceHtml .= "<span>{$currentOnlinePrice}</span><br />";
            }

            return $priceHtml;
        }

        $onlinePrice = $row->getData('online_price');
        if ((float)$onlinePrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';
        } else {
            $priceValue = Mage::app()->getLocale()->currency($currency)->toCurrency($onlinePrice);
        }

        return $priceValue;
    }

    // ----------------------------------------

    private function getLockedTag($row)
    {
        $html = '';
        $childCount = 0;

        $tempLocks = $this->getLockedData($row);
        foreach ($tempLocks['object_locks'] as $lock) {

            switch ($lock->getTag()) {

                case 'list_action':
                    $html .= '<br/><span style="color: #605fff">[List in Progress...]</span>';
                    break;

                case 'relist_action':
                    $html .= '<br/><span style="color: #605fff">[Relist in Progress...]</span>';
                    break;

                case 'revise_action':
                    $html .= '<br/><span style="color: #605fff">[Revise in Progress...]</span>';
                    break;

                case 'stop_action':
                    $html .= '<br/><span style="color: #605fff">[Stop in Progress...]</span>';
                    break;

                case 'stop_and_remove_action':
                    $html .= '<br/><span style="color: #605fff">[Stop And Remove in Progress...]</span>';
                    break;

                case 'delete_and_remove_action':
                    $html .= '<br/><span style="color: #605fff">[Remove in Progress...]</span>';
                    break;

                case 'child_products_in_action':
                    $childCount++;
                    break;

                default:
                    break;
            }
        }

        if ($childCount > 0) {
            $html .= '<br/><span style="color: #605fff">[Child(s) in Action...]</span>';
        }

        return $html;
    }

    private function getLockedData($row)
    {
        $listingProductId = $row->getData('id');
        if (!isset($this->lockedDataCache[$listingProductId])) {
            $objectLocks = Mage::getModel('M2ePro/Listing_Product')->load($listingProductId)->getProcessingLocks();
            $tempArray = array(
                'object_locks' => $objectLocks,
                'in_action' => !empty($objectLocks),
            );
            $this->lockedDataCache[$listingProductId] = $tempArray;
        }

        return $this->lockedDataCache[$listingProductId];
    }

    // ---------------------------------------

    private function getScheduledTag($row)
    {
        $html = '';

        $scheduledActionsCollection = Mage::getResourceModel('M2ePro/Listing_Product_ScheduledAction_Collection');
        $scheduledActionsCollection->addFieldToFilter('listing_product_id', $row['id']);

        /** @var Ess_M2ePro_Model_Listing_Product_ScheduledAction $scheduledAction */
        $scheduledAction = $scheduledActionsCollection->getFirstItem();
        if (!$scheduledAction->getId()) {
            return $html;
        }

        switch ($scheduledAction->getActionType()) {

            case Ess_M2ePro_Model_Listing_Product::ACTION_LIST:
                $html .= '<br/><span style="color: #605fff">[List is Scheduled...]</span>';
                break;

            case Ess_M2ePro_Model_Listing_Product::ACTION_RELIST:
                $html .= '<br/><span style="color: #605fff">[Relist is Scheduled...]</span>';
                break;

            case Ess_M2ePro_Model_Listing_Product::ACTION_REVISE:

                $reviseParts = array();

                $additionalData = $scheduledAction->getAdditionalData();
                if (!empty($additionalData['configurator'])) {
                    $configurator = Mage::getModel('M2ePro/Walmart_Listing_Product_Action_Configurator');
                    $configurator->setData($additionalData['configurator']);

                    if ($configurator->isIncludingMode()) {
                        if ($configurator->isQtyAllowed()) {
                            $reviseParts[] = 'QTY';
                        }

                        if ($configurator->isPriceAllowed()) {
                            $reviseParts[] = 'Price';
                        }

                        if ($configurator->isPromotionsAllowed()) {
                            $reviseParts[] = 'Promotions';
                        }

                        if ($configurator->isDetailsAllowed()) {

                            $params = $additionalData['params'];

                            if (isset($params['changed_sku'])) {
                                $reviseParts[] = 'SKU';
                            }

                            if (isset($params['changed_identifier'])) {
                                $reviseParts[] = strtoupper($params['changed_identifier']['type']);
                            }

                            $reviseParts[] = 'Details';
                        }
                    }
                }

                if (!empty($reviseParts)) {
                    $reviseParts = implode(', ', $reviseParts);
                    $html .= '<br/><span style="color: #605fff">[Revise of '.$reviseParts.' is Scheduled...]</span>';
                } else {
                    $html .= '<br/><span style="color: #605fff">[Revise is Scheduled...]</span>';
                }

                break;

            case Ess_M2ePro_Model_Listing_Product::ACTION_STOP:
                $html .= '<br/><span style="color: #605fff">[Stop is Scheduled...]</span>';
                break;

            case Ess_M2ePro_Model_Listing_Product::ACTION_DELETE:
                $html .= '<br/><span style="color: #605fff">[Retire is Scheduled...]</span>';
                break;

            default:
                break;

        }

        return $html;
    }

    //########################################

    protected function callbackFilterProductId($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter('entity_id', $cond);
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            array(
                array('attribute'=>'sku','like'=>'%'.$value.'%'),
                array('attribute'=>'name', 'like'=>'%'.$value.'%'),
                array('attribute'=>'listing_title','like'=>'%'.$value.'%'),
            )
        );
    }

    protected function callbackFilterOnlineSku($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('wlp.sku LIKE ?', '%'.$value.'%');
    }

    protected function callbackFilterGtin($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = <<<SQL
wlp.gtin LIKE '%{$value}%' OR
wlp.upc LIKE '%{$value}%' OR
wlp.ean LIKE '%{$value}%' OR
wlp.isbn LIKE '%{$value}%' OR
wlp.wpid LIKE '%{$value}%' OR
wlp.item_id LIKE '%{$value}%'
SQL;

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $quoted = $collection->getConnection()->quote($value['from']);
            $where .= 'online_qty >= ' . $quoted;
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $quoted = $collection->getConnection()->quote($value['to']);
            $where .= 'online_qty <= ' . $quoted;
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $condition = '';

        if (isset($value['from']) || isset($value['to'])) {

            if (isset($value['from']) && $value['from'] != '') {
                $condition = 'wlp.online_price >= \'' . (float)$value['from'] . '\'';
            }

            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'wlp.online_price <= \'' . (float)$value['to'] . '\'';
            }
        }

        $collection->getSelect()->where($condition);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            "status = {$value} OR (variation_child_statuses REGEXP '\"{$value}\":[^0]') AND is_variation_parent = 1"
        );
    }

    //########################################
}