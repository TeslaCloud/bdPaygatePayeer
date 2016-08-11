<?php

class bdPaygatePayeer_XenForo_ControllerAdmin_UserUpgrade extends XFCP_bdPaygatePayeer_XenForo_ControllerAdmin_UserUpgrade
{
    public function actionIndex()
    {
        $optionModel = $this->getModelFromCache('XenForo_Model_Option');
        $optionModel->bdPaygatePayeer_hijackOptions();

        return parent::actionIndex();
    }
}