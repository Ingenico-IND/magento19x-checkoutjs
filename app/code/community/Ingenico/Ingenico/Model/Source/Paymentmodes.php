<?php

/**
 * Custom Options for ingenico backend configuration for Payment Modes
 **/

class Ingenico_Ingenico_Model_Source_Paymentmodes extends Mage_Adminhtml_Block_System_Config_Form_Field

{
    protected $_options;

    public function toOptionArray()
    {
        $payment_modes = array(
            array('value' => 'all', 'label' => 'All'),
            array('value' => 'cards', 'label' => 'Cards'),
            array('value' => 'netBanking', 'label' => 'NetBanking'),
            array('value' => 'UPI', 'label' => 'UPI'),
            array('value' => 'imps', 'label' => 'Imps'),
            array('value' => 'wallets', 'label' => 'Wallets'),
            array('value' => 'cashCards', 'label' => 'CashCards'),
            array('value' => 'NEFTRTGS', 'label' => 'NEFTRTGS'),
            array('value' => 'emiBanks', 'label' => 'EmiBanks'),
        );

        return $payment_modes;
    }
}
