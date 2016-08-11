<?php

class bdPaygatePayeer_Processor extends bdPaygate_Processor_Abstract
{
    const CURRENCY_RUB = 'rub';

    public function getSupportedCurrencies()
    {
        $currencies = array();
        $currencies[] = self::CURRENCY_RUB;
        $currencies[] = self::CURRENCY_USD;
        $currencies[] = self::CURRENCY_EUR;

        return $currencies;
    }

    public function isAvailable()
    {
        $options = XenForo_Application::getOptions();
        // Payeer не поддерживает тестовый режим,
        // поэтому на всякий случай отключаем, если включён "Sandbox"
        if (empty($options->bdPaygatePayeer_ID) || empty($options->bdPaygatePayeer_SecretKey) || $this->_sandboxMode()) {
            return false;
        }

        return true;
    }

    public function isRecurringSupported()
    {
        return false;
    }

    public function validateCallback(Zend_Controller_Request_Http $request, &$transactionId, &$paymentStatus, &$transactionDetails = array(), &$itemId)
    {
        // TODO: Пофиксить алгоритм
        $input = new XenForo_Input($request);
        $transactionDetails = $input->getInput();

        $signature = $transactionDetails['m_sign'];

        $transactionId = (!empty($transactionDetails['m_operation_id']) ? ('payeer_' . $transactionDetails['m_operation_id']) : '');
        $paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_OTHER;

        $processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');
        $options = XenForo_Application::get('options');
        $payeer_key = $options->bdPaygatePayeer_SecretKey;

        // Проверяем, не была ли уже проведена такая операция
        $log = $processorModel->getLogByTransactionId($transactionId);
        if (!empty($log)) {
            $this->_setError("Transaction {$transactionId} has already been processed");
            echo $transactionDetails['m_orderid'].'|error';
            return false;
        }

        // Генерация подписи
        $crc = array(
            $transactionDetails['m_operation_id'],
            $transactionDetails['m_operation_ps'],
            $transactionDetails['m_operation_date'],
            $transactionDetails['m_operation_pay_date'],
            $transactionDetails['m_shop'],
            $transactionDetails['m_orderid'],
            $transactionDetails['m_amount'],
            $transactionDetails['m_curr'],
            $transactionDetails['m_desc'],
            $transactionDetails['m_status'],
            $payeer_key
        );
        $crc = strtoupper(hash('sha256', implode(':', $crc)));


        // Сверяем нашу подпись с той, которую мы получили
        if ($crc != $signature) {
            $this->_setError('Request not validated + ' . $crc . ' + ' . $signature);
            return false;
        }

        // https://www.walletone.com/ru/merchant/documentation/#step5
        switch ($transactionDetails['m_status']) {
            case "success":
                // Платеж успешно проведен
                $itemId = base64_decode($transactionDetails['m_orderid']);
                $paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_ACCEPTED;
                echo $transactionDetails['m_orderid'].'|success';
                break;
            default:
                $paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_REJECTED;
                echo $transactionDetails['m_orderid'].'|success';
        }

        return true;
    }

    public function generateFormData($amount, $currency, $itemName, $itemId, $recurringInterval = false, $recurringUnit = false, array $extraData = array())
    {
        $this->_assertAmount($amount);
        $this->_assertCurrency($currency);
        $this->_assertItem($itemName, $itemId);
        $this->_assertRecurring($recurringInterval, $recurringUnit);

        $formAction = 'https://payeer.com/merchant/';
        $callToAction = new XenForo_Phrase('bdpaygate_payeer_call_to_action');

        $options = XenForo_Application::get('options');
        $payeer_key = $options->bdPaygatePayeer_SecretKey;

        $payment = array(
            'm_shop'        => $options->bdPaygatePayeer_ID,
            'm_orderid'     => base64_encode($itemId),
            'm_amount'      => $amount,
            'm_curr'        => utf8_strtoupper($currency),
            'm_desc'        => base64_encode($itemName),
            'm_sign'        => $payeer_key
        );

        // Генерация подписи для формы
        $payment['m_sign'] = strtoupper(hash('sha256', implode(":", $payment)));

        // Генерация формы
        $form = "<form action='{$formAction}' method='POST'>";
        foreach ($payment as $item => $value){
            $form .= "<input type='hidden' name='$item' value='$value' />";
        }
        $form .= "<input type='submit' value='{$callToAction}' class='button'/></form>";

        return $form;
    }
}