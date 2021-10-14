<?php

/**
 * Custom Options for paynimo backend configuration for Txntype
 **/

class Worldline_Worldline_Model_Source_Txntype extends Mage_Adminhtml_Block_System_Config_Form_Field

{
    protected $_options;

    public function toOptionArray()
    {
        $trans_req = array(
            array('value' => 'SALE', 'label' => 'SALE'),
        );

        return $trans_req;
    }
}
