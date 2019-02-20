<?php
namespace mozzler\base\commands;

use Yii;
use yii\console\Controller;
class BaseController extends Controller {

    /**
     * Disable permission system so commands have unrestricted access to the system
     */
    public function init()
    {
        parent::init();

        \Yii::$app->rbac->forceAdmin = true;
    }

}