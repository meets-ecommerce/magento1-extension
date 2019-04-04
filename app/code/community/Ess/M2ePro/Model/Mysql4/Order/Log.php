<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Mysql4_Order_Log
    extends Ess_M2ePro_Model_Mysql4_Log_Abstract
{
    //########################################

    public function _construct()
    {
        $this->_init('M2ePro/Order_Log', 'id');
    }

    //########################################
}