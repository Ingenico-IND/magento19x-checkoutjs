<?php
class Ingenico_Ingenico_Adminhtml_ReconciliationController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();

        $this->_title('Reconciliation');

        $this->_setActiveMenu('ingenico/reconciliation');

        $this->_addContent(
            $this->getLayout()->createBlock('ingenico/reconciliation', 'reconciliation')
        );

        $this->renderLayout();
    }
}
