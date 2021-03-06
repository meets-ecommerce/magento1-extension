<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Ebay_Listing_PickupStore_Variation_Product_View
    extends Ess_M2ePro_Block_Adminhtml_Widget_Container
{
    protected $listingProductId;
    protected $locationId;

    //########################################

    /**
     * @param mixed $listingProductId
     * @return $this
     */
    public function setListingProductId($listingProductId)
    {
        $this->listingProductId = $listingProductId;

        return $this;
    }
    /**
     * @return mixed
     */
    public function getListingProductId()
    {
        return $this->listingProductId;
    }

    // ---------------------------------------

    /**
     * @param $locationId
     */
    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;
    }

    /**
     * @return mixed
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    //########################################

    public function __construct()
    {
        parent::__construct();

        $this->setTemplate('M2ePro/ebay/listing/pickupStore/variation/product/view.phtml');

        return $this;
    }

    protected function _toHtml()
    {
        $data = array(
            'style' => 'float: right; margin-top: 7px; ',
            'label'   => Mage::helper('M2ePro')->__('Close'),
            'onclick' => 'EbayListingPickupStoreGridHandlerObj.closeVariationPopUp()'
        );
        $closeBtn = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);

        $additionalJavascript = <<<HTML
        <script type="text/javascript">
            EbayListingPickupStoreGridHandlerObj.loadVariationsGrid(true);
        </script>
HTML;

        return parent::_toHtml() . $additionalJavascript . $closeBtn->toHtml();
    }

    //########################################
}
