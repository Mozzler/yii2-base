<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;

class ActiveIndexAction extends \yii\rest\IndexAction
{
    
    public $scenario = [Model::SCENARIO_LIST_API, Model::SCENARIO_LIST];
    public $resultScenario = [Model::SCENARIO_LIST_API, Model::SCENARIO_LIST];

}
