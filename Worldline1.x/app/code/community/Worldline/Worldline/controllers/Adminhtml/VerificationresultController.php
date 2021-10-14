<?php
class Worldline_Worldline_Adminhtml_VerificationresultController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();

        $this->_title('Offline Verification');

        $this->_setActiveMenu('Worldline/verification');

        $this->_addContent(
            $this->getLayout()->createBlock('Worldline/verification', 'verification')
        );

        $this->renderLayout();
    }
}
