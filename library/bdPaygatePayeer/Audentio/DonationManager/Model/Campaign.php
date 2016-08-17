<?php

class bdPaygatePayeer_Audentio_DonationManager_Model_Campaign extends XFCP_bdPaygatePayeer_Audentio_DonationManager_Model_Campaign
{
    public function getCurrencies($currency = false)
    {
        $currencies = parent::getCurrencies();

        $currencies[bdPaygatePayeer_Processor::CURRENCY_RUB] = array(
            'name' => 'RUB',
            'suffix' => '₽'
        );

        if ($currency && array_key_exists($currency, $currencies)) {
            return $currencies[$currency];
        }

        return $currencies;
    }
}