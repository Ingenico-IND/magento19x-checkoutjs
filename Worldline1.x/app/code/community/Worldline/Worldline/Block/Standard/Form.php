<?php

class Worldline_Worldline_Block_Standard_Form extends Mage_Payment_Block_Form
{
    protected $_methodCode = 'Worldline';

    protected function _construct()
    {
        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $mark->setTemplate('Worldline/payment/mark.phtml'); // known issue: code above will render only static mark image
        $this->setMethodTitle('')
            ->setMethodLabelAfterHtml($mark->toHtml());
        return parent::_construct();
    }
}
