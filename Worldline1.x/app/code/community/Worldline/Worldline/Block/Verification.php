<?php

class Worldline_Worldline_Block_Verification extends Mage_Adminhtml_Block_Template
{
    protected $data = null;

    public function __construct()
    {
        $this->performORequest();
        $this->setTemplate("Worldline/verification.phtml");
        parent::__construct();
    }

    public function getResultUrl()
    {
        return $this->getUrl('Worldline_admin/adminhtml_verificationresult');
    }

    private function getMerchantCode()
    {
        return Mage::getStoreConfig('payment/Worldline/Worldline_mercode');
    }

    private function performORequest()
    {
        $data = Mage::app()->getRequest()->getPost();
        if (!isset($data['merchant_transaction_id'])) {
            return false;
        }

        $merchant_identifier = $this->getMerchantCode();
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();

        $date_time = date('d-m-Y', strtotime($data['txn_date']));
        $merchant_transaction_id = $data['merchant_transaction_id'];

        $request_array = [
            "merchant" => [
                "identifier" => $merchant_identifier
            ],
            "transaction" => [
                "deviceIdentifier" => "S",
                "currency" => $currency_code,
                "dateTime" => $date_time,
                "identifier" => $merchant_transaction_id,
                "requestType" => "O"
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
        $result      = file_get_contents($url, false, $context);
        $paymentResult = json_decode($result);

        $data = [
            'status_code' => $paymentResult->paymentMethod->paymentTransaction->statusCode,
            'status_message' => $paymentResult->paymentMethod->paymentTransaction->statusMessage,
            'message' => $paymentResult->paymentMethod->paymentTransaction->errorMessage,
            'merchant_transaction_id' => $paymentResult->merchantTransactionIdentifier,
            'tpsl_transaction_id' => $paymentResult->paymentMethod->paymentTransaction->identifier,
            'amount' => $paymentResult->paymentMethod->paymentTransaction->amount,
            'date_time' => $paymentResult->paymentMethod->paymentTransaction->dateTime,
        ];

        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
