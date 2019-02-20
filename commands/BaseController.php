<?php
namespace mozzzler\base\commands;

class BaseController {

    /**
     * Disable permission system so commands have unrestricted access to the system
     */
    public function init()
    {
        parent::init();

        \Yii::$app->rbac->forceAdmin = true;
    }

}