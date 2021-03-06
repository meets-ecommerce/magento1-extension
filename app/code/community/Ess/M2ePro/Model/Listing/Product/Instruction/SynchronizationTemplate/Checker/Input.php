<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Listing_Product_Instruction_SynchronizationTemplate_Checker_Input
    extends Ess_M2ePro_Model_Listing_Product_Instruction_Handler_Input
{
    /** @var Ess_M2ePro_Model_Listing_Product_ScheduledAction */
    private $scheduledAction = NULL;

    //########################################

    public function setScheduledAction(Ess_M2ePro_Model_Listing_Product_ScheduledAction $scheduledAction)
    {
        $this->scheduledAction = $scheduledAction;
        return $this;
    }

    public function getScheduledAction()
    {
        return $this->scheduledAction;
    }

    //########################################
}