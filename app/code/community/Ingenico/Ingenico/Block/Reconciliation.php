<?php

class Ingenico_Ingenico_Block_Reconciliation extends Mage_Adminhtml_Block_Template
{
    protected $message = '';

    public function __construct()
    {
        $this->performReconciliation();
        $this->setTemplate("ingenico/reconciliation.phtml");
        parent::__construct();
    }

    public function getResultUrl()
    {
        return $this->getUrl('ingenico_admin/adminhtml_reconciliationresult');
    }

    private function getMerchantCode()
    {
        return Mage::getStoreConfig('payment/ingenico/ingenico_mercode');
    }

    public function getMaxFromDate()
    {
        return date('Y-m-d', strtotime("-1 days"));
    }

    public function getMaxToDate()
    {
        return date('Y-m-d');
    }

    protected function performReconciliation()
    {
        $data = Mage::app()->getRequest()->getPost();
        if (!isset($data['from_date'])) {
            return false;
        }

        $from_date = date('Y-m-d ' . '00:00:00', strtotime($this->getRequest()->getParam('from_date')));
        $to_date = date('Y-m-d ' . "23:59:59", strtotime($this->getRequest()->getParam('to_date')));

        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();

        $orderIds = $this->getOrderCollectionByDate($from_date, $to_date);
        $merchant_identifier = $this->getMerchantCode();

        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_read');

        $tableName = $resource->getTableName('sales_payment_transaction');

        $successFullOrdersIds = [];

        foreach ($orderIds as $orderId) {

            $sql = "select txn_id,created_at from " . $tableName . " where order_id=" . $orderId . " and txn_type='capture' order by created_at asc limit 1 ";

            $result = $connection->query($sql);

            $data = $result->fetch();

            if (!$data) {
                continue;
            }

            $merchant_transaction_id = $data['txn_id'];
            $transaction_date = date('d-m-Y', strtotime($data['created_at']));;

            $request_array = [
                "merchant" => [
                    "identifier" => $merchant_identifier
                ],
                "transaction" => [
                    "deviceIdentifier" => "S",
                    "currency" => $currency_code,
                    "dateTime" => $transaction_date,
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

            if ($this->isPaymentSuccessful($paymentResult)) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $order->setData('state', "processing");
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
                $order->setEmailSent(true);
                $payment = $order->getPayment();
                $payment->setTransactionId($this->getTPSLTransactionId($paymentResult));
                $transaction = $payment->addTransaction('order', null, false, 'order placed');
                $transaction->setParentTxnId(null);
                $transaction->setIsClosed(0);

                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                $invoice->setTransactionId($this->getTPSLTransactionId($paymentResult));
                $invoice->register();
                $invoice->getOrder()->setIsInProcess(true);
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());

                $transactionSave->save();
                $order->save();

                array_push($successFullOrdersIds, $orderId);
            } else if ($this->isPaymentFailed($paymentResult)) {
                $order = Mage::getModel('sales/order')->load($orderId);
                if ($order->getId()) {
                    // Flag the order as 'cancelled' and save it			
                    $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
                }
                array_push($successFullOrdersIds, $orderId);
            }
        }
        if ($successFullOrdersIds) {
            $this->message = "Updated Order Status for Order ID " . implode(",", $successFullOrdersIds);
        } else {
            $this->message = "Found  no orders to update";
        }
    }

    public function getOrderCollectionByDate($from, $to)
    {
        $collection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter(
                'status',
                ['in' => 'pending']
            )
            ->addFieldToFilter(
                'created_at',
                ['gteq' => $from]
            )
            ->addFieldToFilter(
                'created_at',
                ['lteq' => $to]
            );

        $collection->getSelect()
            ->join(
                ["sop" => "sales_flat_order_payment"],
                'main_table.entity_id = sop.parent_id',
                array('method')
            )
            ->where('sop.method = ?', "ingenico");

        $collection->setOrder(
            'created_at',
            'desc'
        );

        try {
            $orderIdArray = $collection->getAllIds();
        } catch (\Exception $th) {
            $orderIdArray = [];
        }
        return $orderIdArray;
    }

    protected function getTPSLTransactionId($paymentResult)
    {
        if ($paymentResult->paymentMethod->paymentTransaction->statusCode == 300) {
            return $paymentResult->paymentMethod->paymentTransaction->identifier;
        }

        return null;
    }

    public function getMessage()
    {
        return $this->message;
    }

    protected function isPaymentSuccessful($paymentResult)
    {
        if ($paymentResult->paymentMethod->paymentTransaction->statusCode == 300) {
            return true;
        }

        return false;
    }

    protected function isPaymentFailed($paymentResult)
    {
        if (
            $paymentResult->paymentMethod->paymentTransaction->statusCode == 392 ||
            $paymentResult->paymentMethod->paymentTransaction->statusCode == 396 ||
            $paymentResult->paymentMethod->paymentTransaction->statusCode == 397 ||
            $paymentResult->paymentMethod->paymentTransaction->statusCode == 399
        ) {
            return true;
        }

        return false;
    }
}
