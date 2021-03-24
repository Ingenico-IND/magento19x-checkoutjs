<?php
class Ingenico_Ingenico_Adminhtml_VerificationController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();

        $this->_title('Offline Verification');

        $this->_setActiveMenu('ingenico/verification');

        $this->_addContent(
            $this->getLayout()->createBlock('ingenico/verification', 'verification')
        );

        $this->renderLayout();
    }
}
