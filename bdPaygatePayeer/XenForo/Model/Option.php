<?php

class bdPaygatePayeer_XenForo_Model_Option extends XFCP_bdPaygatePayeer_XenForo_Model_Option
{
    // this property must be static because XenForo_ControllerAdmin_UserUpgrade::actionIndex
    // for no apparent reason use XenForo_Model::create to create the optionModel
    // (instead of using XenForo_Controller::getModelFromCache)
    private static $_bdPaygatePayeer_hijackOptions = false;

    public function getOptionsByIds(array $optionIds, array $fetchOptions = array())
    {
        if (self::$_bdPaygatePayeer_hijackOptions === true)
        {
            $optionIds[] = 'bdPaygatePayeer_ID';
            $optionIds[] = 'bdPaygatePayeer_SecretKey';
        }

        $options = parent::getOptionsByIds($optionIds, $fetchOptions);

        self::$_bdPaygatePayeer_hijackOptions = false;

        return $options;
    }

    public function bdPaygatePayeer_hijackOptions()
    {
        self::$_bdPaygatePayeer_hijackOptions = true;
    }
}