<?php
class Worldline_Worldline_Adminhtml_ReconciliationController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();

        $this->_title('Reconciliation');

        $this->_setActiveMenu('Worldline/reconciliation');

        $this->_addContent(
            $this->getLayout()->createBlock('Worldline/reconciliation', 'reconciliation')
        );

        $this->renderLayout();
    }
}
