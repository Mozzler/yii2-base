<?php
namespace mozzler\base\actions;
use mozzler\base\models\Model;

class ActiveUpdateAction extends \yii\rest\UpdateAction
{

    public $scenario = [Model::SCENARIO_UPDATE_API, Model::SCENARIO_UPDATE];
    public $resultScenario = [Model::SCENARIO_VIEW_API, Model::SCENARIO_VIEW];
    
}