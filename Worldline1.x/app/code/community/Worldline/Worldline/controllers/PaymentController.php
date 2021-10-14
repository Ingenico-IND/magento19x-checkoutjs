<?php

/**
 * Worldline Payment Standard Checkout Controller
 **/

require_once(Mage::getBaseDir() . '/app/code/community/Worldline/Worldline/Model/Standard.php');

class Worldline_Worldline_PaymentController extends Mage_Core_Controller_Front_Action
{
	public function getDebug()
	{
		return Mage::getSingleton('Worldline/config')->getDebug();
	}

	/**
	 * When a customer chooses Worldline on Checkout/Payment page
	 *
	 */
	public function redirectAction()

	{
		$this->loadLayout();
		$block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'test', array('template' => 'Worldline/Worldline.phtml'));
		$this->getLayout()->getBlock('content')->append($block);
		$this->renderLayout();
	}

	// The response action is triggered when your gateway sends back a response after processing the customer's payment
	public function responseAction()
	{
		$data = Mage::app()->getRequest()->getParams();
		$str = $data['msg'];
		$responseDetails = explode('|', $str);
		if ($responseDetails[0] != '') {
			$MrctCode = Mage::getStoreConfig('payment/Worldline/Worldline_mercode');
			$CurrencyType = Mage::app()->getStore()->getCurrentCurrencyCode();
			$responsedate = explode(' ', $responseDetails[8]);
			$data_array = array(
				"merchant" => array(
					"identifier" => $MrctCode

				),
				"transaction" => array(
					"deviceIdentifier" => "S",
					"currency" => $CurrencyType,
					"dateTime" => $responsedate[0],
					"token" => $responseDetails[5],
					"requestType" => "S"
				)
			);
			$url = "https://www.paynimo.com/api/paynimoV2.req";
			$json_data = json_encode($data_array);
			$client = new Zend_Http_Client($url);
			$client->setRawData($json_data, 'application/json')->request('POST');
			$scallResponse = json_decode($client->request()->getBody());
			$scallStatusCode = $scallResponse->paymentMethod->paymentTransaction->statusCode;
			$txn_msg  = $this->getErrorStatusMessage($responseDetails[0]);
			if (!$txn_msg) {
				$txn_msg = $responseDetails[1];
			}
			$txn_err_msg = $responseDetails[2];
			if (!$txn_err_msg) {
				$txn_err_msg = 'Transaction Failed';
			}
			$oid = explode('orderid:', $responseDetails[7]);
			$oid_1 = $oid[1];
			$oid2 = explode('}', $oid_1);
			$oidreceived = $oid2[0];
			$orderId = $oidreceived;
			if ($orderId == '') {
				$orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
			}
			$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
			$key = Mage::getStoreConfig('payment/Worldline/Worldline_key');
			$verificationHash = array_pop($responseDetails);
			$hashableString = join('|', $responseDetails) . "|" . $key;
			$hashedString = hash('sha512',  $hashableString);
			Mage::log('Response: ' . $str, null, 'Worldline_' . date("Ymd") . '.log', true);
			$order = Mage::getModel('sales/order');
			$order->loadByIncrementId($orderId);
			$payment = $order->getPayment();
			$payment->setTransactionId($responseDetails[5]);
			$transaction = $payment->addTransaction('order', null, false, 'order placed');
			$transaction->setParentTxnId(null);
			$transaction->setIsClosed(0);
			$transaction->save();
			if ($hashedString == $verificationHash) {


				if ($responseDetails[0] == '0300' && $scallStatusCode == '0300') {
					$order->setData('state', 'processing');
					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
					$order->sendNewOrderEmail();
					$order->setEmailSent(true);
					$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->setTransactionId($responseDetails[5]);
					$invoice->register()->pay();
					$invoice->getOrder()->setIsInProcess(true);
					$transactionSave = Mage::getModel('core/resource_transaction')
						->addObject($invoice)
						->addObject($invoice->getOrder());
					$transactionSave->save();

					$order->save();
					Mage::getSingleton('checkout/session')->unsQuoteId();
					Mage::getSingleton('core/session')->addSuccess('Worldline Message: ' . $txn_msg);
					Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure' => true));
				} else {
					//failed

					$this->cancelAction($orderId);
					Mage::getSingleton('core/session')->addError('Transaction Status: ' . $txn_msg . '<br> Transaction Error Message from Payment Gateway: ' . $txn_err_msg);
					Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
				}
			} else {
				$this->cancelAction($orderId);
				Mage::getSingleton('core/session')->addError('Payment Failed Hash Verification Failed!');
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
			}
		} else {
			$orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
			$this->cancelAction($orderId);
			Mage::getSingleton('core/session')->addError('Payment Failed Empty Response!');
			Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
		}
	}

	/**
	 * When a customer cancel payment from Worldline.
	 */

	// The cancel action is triggered when an order is to be cancelled
	public function cancelAction($orderId)
	{
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
		if ($order->getId()) {
			// Flag the order as 'cancelled' and save it			
			$order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
		}
	}

	protected function getErrorStatusMessage($code)
	{
		$messages = [
			"0300" => "Successful Transaction",
			"0392" => "Transaction cancelled by user either in Bank Page or in PG Card /PG Bank selection",
			"0396" => "Transaction response not received from Bank, Status Check on same Day",
			"0397" => "Transaction Response not received from Bank. Status Check on next Day",
			"0399" => "Failed response received from bank",
			"0400" => "Refund Initiated Successfully",
			"0401" => "Refund in Progress (Currently not in used)",
			"0402" => "Instant Refund Initiated Successfully(Currently not in used)",
			"0499" => "Refund initiation failed",
			"9999" => "Transaction not found :Transaction not found in PG"
		];

		if (in_array($code, array_keys($messages))) {
			return $messages[$code];
		}

		return null;
	}

	public function s2sverificationAction()

	{
		$data = Mage::app()->getRequest()->getParams();
		$str = $data['msg'];
		if ($str) {
			$responseDetails = explode('|', $str);
			$MrctCode = Mage::getStoreConfig('payment/Worldline/Worldline_mercode');
			$oid = explode('orderid:', $responseDetails[7]);
			$oid_1 = $oid[1];
			$oid2 = explode('}', $oid_1);
			$oidreceived = $oid2[0];
			$orderId = $oidreceived;
			$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
			$key = Mage::getStoreConfig('payment/Worldline/Worldline_key');
			$verificationHash = array_pop($responseDetails);
			$hashableString = join('|', $responseDetails) . "|" . $key;
			$hashedString = hash('sha512',  $hashableString);
			Mage::log('Response S2S: ' . $str, null, 'Worldline_' . date("Ymd") . '.log', true);
			$order = Mage::getModel('sales/order');
			$order->loadByIncrementId($orderId);
			$payment = $order->getPayment();
			$payment->setTransactionId($responseDetails[5]);
			$transaction = $payment->addTransaction('order', null, false, 'order placed');
			$transaction->setParentTxnId(null);
			$transaction->setIsClosed(0);
			$transaction->save();
			if ($responseDetails[0] == '0300' && $hashedString == $verificationHash) {
				//success
				$successstatus = Mage::getStoreConfig('payment/Worldline/order_status');
				$order->setData('state', $successstatus);
				$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
				$order->sendNewOrderEmail();
				$order->setEmailSent(true);
				$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
				$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
				$invoice->setTransactionId($responseDetails[5]);
				$invoice->register()->pay();
				$invoice->getOrder()->setIsInProcess(true);
				$transactionSave = Mage::getModel('core/resource_transaction')
					->addObject($invoice)
					->addObject($invoice->getOrder());
				$transactionSave->save();

				$order->save();
				echo json_encode($responseDetails[3] . "|" . $responseDetails[5] . "|1");
				die;
			} else {
				//failed

				$this->cancelAction($orderId);
				echo json_encode($responseDetails[3] . "|" . $responseDetails[5] . "|0");
				die;
			}
		} else {
			echo json_encode("INVALID DATA");
			die;
		}
	}

	public function restorequoteAction()
	{
		$order = Mage::getSingleton('checkout/session')->getLastRealOrder();
		if ($order) {
			$quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
			if ($quote->getId()) {
				$quote->setIsActive(1)
					->setReservedOrderId(null)
					->save();
				Mage::getSingleton('checkout/session')
					->replaceQuote($quote)
					->unsLastRealOrderId();
				echo json_encode(1);
			}
		} else {
			echo json_encode(0);
		}
	}
}
