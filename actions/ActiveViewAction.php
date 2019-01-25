<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;

class ActiveViewAction extends \yii\rest\ViewAction
{
    public $scenario = [Model::SCENARIO_VIEW_API, Model::SCENARIO_VIEW];
    public $resultScenario = [Model::SCENARIO_VIEW_API, Model::SCENARIO_VIEW];
}
