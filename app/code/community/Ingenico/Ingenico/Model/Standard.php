<?php

/**
 * Ingenico Standard Checkout Module
 **/

class Ingenico_Ingenico_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
	protected $_code = 'ingenico';
	protected $_isInitializeNeeded      = true;
	protected $_formBlockType = 'ingenico/standard_form';
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;
	protected $_canRefund = true;
	protected $_canRefundInvoicePartial     = true;

	/**
	 * Transaction Request getter for payment module
	 */

	public function getRequest()
	{
		$order =  Mage::getSingleton('checkout/session')->getLastRealOrder();
		$orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		$order1 = Mage::getModel('sales/order')->loadByIncrementId($orderId);
		if ($order1 && $order1->getId()) {
			$billingAddress = $order1->getBillingAddress();
			$custFullname = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
			$custMobno = $billingAddress->getTelephone();
			$custemail = $billingAddress->getEmail();
		}
		if (strpos($custMobno, '+') !== false) {
			$custMobno = str_replace("+", "", $custMobno);
		}
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$key = Mage::getStoreConfig('payment/ingenico/ingenico_key');
		$merchantMsg = Mage::getStoreConfig('payment/ingenico/merchantMsg');
		$txnType = Mage::getStoreConfig('payment/ingenico/txnType');
		$disclaimerMsg = Mage::getStoreConfig('payment/ingenico/disclaimerMsg');
		if (Mage::getStoreConfig('payment/ingenico/paymentModeOrder')) {
			$paymentModeOrder = Mage::getStoreConfig('payment/ingenico/paymentModeOrder');
			$paymentorderarray = explode(',', $paymentModeOrder);
			$paymentModeOrder_1 = isset($paymentorderarray[0]) ? $paymentorderarray[0] : null;
			$paymentModeOrder_2 = isset($paymentorderarray[1]) ? $paymentorderarray[1] : null;
			$paymentModeOrder_3 = isset($paymentorderarray[2]) ? $paymentorderarray[2] : null;
			$paymentModeOrder_4 = isset($paymentorderarray[3]) ? $paymentorderarray[3] : null;
			$paymentModeOrder_5 = isset($paymentorderarray[4]) ? $paymentorderarray[4] : null;
			$paymentModeOrder_6 = isset($paymentorderarray[5]) ? $paymentorderarray[5] : null;
			$paymentModeOrder_7 = isset($paymentorderarray[6]) ? $paymentorderarray[6] : null;
			$paymentModeOrder_8 = isset($paymentorderarray[7]) ? $paymentorderarray[7] : null;
			$paymentModeOrder_9 = isset($paymentorderarray[8]) ? $paymentorderarray[8] : null;
			$paymentModeOrder_10 = isset($paymentorderarray[9]) ? $paymentorderarray[9] : null;
		} else {
			$paymentModeOrder_1 = "cards";
			$paymentModeOrder_2 = "netBanking";
			$paymentModeOrder_3 = "imps";
			$paymentModeOrder_4 = "wallets";
			$paymentModeOrder_5 = "cashCards";
			$paymentModeOrder_6 =  "UPI";
			$paymentModeOrder_7 =  "MVISA";
			$paymentModeOrder_8 = "debitPin";
			$paymentModeOrder_9 = "emiBanks";
			$paymentModeOrder_10 = "NEFTRTGS";
		}
		$enableExpressPay = Mage::getStoreConfig('payment/ingenico/enableExpressPay');
		if ($enableExpressPay == '1') {
			$enableExpressPay = true;
		} else {
			$enableExpressPay = false;
		}
		$separateCardMode = Mage::getStoreConfig('payment/ingenico/separateCardMode');
		if ($separateCardMode == '1') {
			$separateCardMode = true;
		} else {
			$separateCardMode = false;
		}
		if (Mage::getStoreConfig('payment/ingenico/primary_color_code')) {
			$primary_color_code = Mage::getStoreConfig('payment/ingenico/primary_color_code');
		} else {
			$primary_color_code = '#3977b7';
		}
		if (Mage::getStoreConfig('payment/ingenico/secondary_color_code')) {
			$secondary_color_code = Mage::getStoreConfig('payment/ingenico/secondary_color_code');
		} else {
			$secondary_color_code = '#FFFFFF';
		}
		if (Mage::getStoreConfig('payment/ingenico/button_color_code_1')) {
			$button_color_code_1 = Mage::getStoreConfig('payment/ingenico/button_color_code_1');
		} else {
			$button_color_code_1 = '#1969bb';
		}
		if (Mage::getStoreConfig('payment/ingenico/button_color_code_2')) {
			$button_color_code_2 = Mage::getStoreConfig('payment/ingenico/button_color_code_2');
		} else {
			$button_color_code_2 = '#FFFFFF';
		}
		$logo_url = Mage::getStoreConfig('payment/ingenico/merchant_logo_url');
		if ($logo_url && @getimagesize($logo_url)) {
			$merchant_logo_url = $logo_url;
		} else {
			$merchant_logo_url = 'https://www.paynimo.com/CompanyDocs/company-logo-md.png';
		}
		$enableNewWindowFlow = Mage::getStoreConfig('payment/ingenico/enableNewWindowFlow');
		if ($enableNewWindowFlow == '1') {
			$enableNewWindowFlow = true;
		} else {
			$enableNewWindowFlow = false;
		}
		$enableInstrumentDeRegistration = Mage::getStoreConfig('payment/ingenico/enableInstrumentDeRegistration');
		if ($enableInstrumentDeRegistration == '1' && $enableExpressPay == '1') {
			$enableInstrumentDeRegistration = true;
		} else {
			$enableInstrumentDeRegistration = false;
		}
		$hideSavedInstruments = Mage::getStoreConfig('payment/ingenico/hideSavedInstruments');
		if ($hideSavedInstruments == '1' && $enableExpressPay == '1') {
			$hideSavedInstruments = true;
		} else {
			$hideSavedInstruments = false;
		}
		$saveInstrument = Mage::getStoreConfig('payment/ingenico/saveInstrument');
		if ($saveInstrument == '1') {
			$saveInstrument = true;
		} else {
			$saveInstrument = false;
		}
		$embedPopup = Mage::getStoreConfig('payment/ingenico/embedPopup');
		if ($embedPopup == '1') {
			$embedPopup = '#ingenicopayment';
		} else {
			$embedPopup = '';
		}
		$paymentmodes = Mage::getStoreConfig('payment/ingenico/paymentmodes');
		$MrctCode = Mage::getStoreConfig('payment/ingenico/ingenico_mercode');
		$MrctsCode = Mage::getStoreConfig('payment/ingenico/ingenico_scode');
		$MrctTxtID = rand(1, 1000000);
		$payment = $order1->getPayment();
		$payment->setTransactionId($MrctTxtID);
		$transaction = $payment->addTransaction('capture', null, false, 'order placed');
		$transaction->setParentTxnId(null);
		$transaction->setIsClosed(0);
		$transaction->save();
		$CurrencyType = Mage::app()->getStore()->getCurrentCurrencyCode();
		$ReturnURL = Mage::getBaseUrl() . 'ingenico/payment/response';
		$restorequoteurl = Mage::getBaseUrl() . 'ingenico/payment/restorequote';
		$carturl = Mage::getBaseUrl() . 'checkout/cart';
		if (Mage::getStoreConfig('payment/ingenico/ingenico_url') == 'test') {
			$Amount = "1.0";
		} else {
			$Amount = round($order->getBaseGrandTotal(), 2);
		}
		$jsarray = array();
		$jsarray['restorequoteurl'] = $restorequoteurl;
		$jsarray['carturl'] = $carturl;
		$jsarray['paymentModeOrder_1'] = $paymentModeOrder_1;
		$jsarray['paymentModeOrder_2'] = $paymentModeOrder_2;
		$jsarray['paymentModeOrder_3'] = $paymentModeOrder_3;
		$jsarray['paymentModeOrder_4'] = $paymentModeOrder_4;
		$jsarray['paymentModeOrder_5'] = $paymentModeOrder_5;
		$jsarray['paymentModeOrder_6'] = $paymentModeOrder_6;
		$jsarray['paymentModeOrder_7'] = $paymentModeOrder_7;
		$jsarray['paymentModeOrder_8'] = $paymentModeOrder_8;
		$jsarray['paymentModeOrder_9'] = $paymentModeOrder_9;
		$jsarray['paymentModeOrder_10'] = $paymentModeOrder_10;
		$jsarray['embedPopup'] = $embedPopup;
		$jsarray['saveInstrument'] = $saveInstrument;
		$jsarray['hideSavedInstruments'] = $hideSavedInstruments;
		$jsarray['txnType'] = $txnType;
		$jsarray['enableNewWindowFlow'] = $enableNewWindowFlow;
		$jsarray['enableInstrumentDeRegistration'] = $enableInstrumentDeRegistration;
		$jsarray['separateCardMode'] = $separateCardMode;
		$jsarray['enableExpressPay'] = $enableExpressPay;
		$jsarray['disclaimerMsg'] = $disclaimerMsg;
		$jsarray['merchantMsg'] = $merchantMsg;
		$jsarray['paymentmodes'] = $paymentmodes;
		$jsarray['primary_color_code'] = $primary_color_code;
		$jsarray['secondary_color_code'] = $secondary_color_code;
		$jsarray['button_color_code_1'] = $button_color_code_1;
		$jsarray['button_color_code_2'] = $button_color_code_2;
		$jsarray['mrctCode'] = $MrctCode;
		$jsarray['CurrencyType'] = $CurrencyType;
		$jsarray['scode'] = $MrctsCode;
		$jsarray['orderId'] = $orderId;
		$jsarray['email'] = $custemail;
		$custID = $customer->getID();
		if (!$custID) {
			$custID = rand(1, 1000000);
		}
		$jsarray['CustomerId'] = 'cons' . $custID;
		$jsarray['CustomerName'] = $custFullname;
		$jsarray['customerMobNumber'] = $custMobno;
		$jsarray['merchant_logo_url'] = $merchant_logo_url;
		$jsarray['key'] = $key;
		$jsarray['merchantTxnRefNumber'] = $MrctTxtID;
		$jsarray['currencyCode'] = $CurrencyType;
		$jsarray['ReturnURL'] = $ReturnURL;
		$jsarray['Amount'] = $Amount;
		$jsarray['SALT'] = $jsarray['key'];
		$datastring = $jsarray['mrctCode'] . "|" . $jsarray['merchantTxnRefNumber'] . "|" . $jsarray['Amount'] . "|" . "|" . $jsarray['CustomerId'] . "|" . $jsarray['customerMobNumber'] . "|" . $jsarray['email'] . "||||||||||" . $jsarray['SALT'];
		$hashed = hash('sha512', $datastring);
		Mage::log('Request: ' . $datastring, null, 'Ingenico_' . date("Ymd") . '.log', true);
		$jsarray['token'] = $hashed;
		return $jsarray;
	}

	public function refund(Varien_Object $payment, $amount)
	{
		if (!$this->canRefund()) {
			Mage::throwException(Mage::helper('payment')->__('Refund action is not available.'));
		}

		$merchant_identifier = $this->getMerchantCode();

		$currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();

		$invoice_date = date("d-m-Y", strtotime($payment->getCreditmemo()->getInvoice()->getCreatedAt()));

		$token = $payment->getCreditmemo()->getInvoice()->getTransactionId();

		$request_array = [
			"merchant" => [
				"identifier" => $merchant_identifier
			],
			"cart" => (new \stdClass()),
			"transaction" => [
				"deviceIdentifier" => "S",
				"amount" => $amount,
				"currency" => $currency_code,
				"dateTime" => $invoice_date,
				"token" => $token,
				"requestType" => "R"
			]
		];

		$url = "https://www.paynimo.com/api/paynimoV2.req";

		$options = array(
			'http' => array(
				'method'  => 'POST',
				'content' => json_encode($request_array),
				'header' =>  "Content-Type: application/json\r\n" .
					"Accept: application/json\r\n"
			)
		);
		$context     = stream_context_create($options);

		$data = json_decode(file_get_contents($url, false, $context));

		if ($data->paymentMethod->paymentTransaction->statusCode != 400) {

			$message = "Ingenico Message: " .
				$data->paymentMethod->paymentTransaction->statusMessage .
				" - " .
				$data->paymentMethod->paymentTransaction->errorMessage;

			Mage::getSingleton('core/session')->addError($message);

			throw new ErrorException(__($message));
		}

		return $this;
	}

	private function getMerchantCode()
	{
		return Mage::getStoreConfig('payment/ingenico/ingenico_mercode');
	}
	/**
	 * Return Order place redirect url
	 *
	 * @return string
	 */

	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('ingenico/payment/redirect', array('_secure' => true));
	}

	/**
	 * Check partial refund availability for invoice
	 *
	 * @return bool
	 */
	public function canRefundPartialPerInvoice()
	{
		return $this->_canRefundInvoicePartial;
	}
}
