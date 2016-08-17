<?php

class bdPaygatePayeer_bdPaygate_Model_Processor extends XFCP_bdPaygatePayeer_bdPaygate_Model_Processor
{
    public function getCurrencies()
    {
        $currencies = parent::getCurrencies();
        $currencies[bdPaygatePayeer_Processor::CURRENCY_RUB] = 'RUB';

        return $currencies;
    }

    public function getProcessorNames()
    {
        $names = parent::getProcessorNames();
        $names['payeer'] = 'bdPaygatePayeer_Processor';

        return $names;
    }
}