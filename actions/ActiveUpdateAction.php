<?php
namespace mozzler\base\actions;
use mozzler\base\models\Model;

class ActiveUpdateAction extends \yii\rest\UpdateAction
{
    public $viewScenario = Model::SCENARIO_VIEW;
    
    public function run($id)
    {
	    $model = parent::run($id);

        $scenario = $this->viewScenario.'-api';

        // check for an "-api" scenario
        if (in_array($scenario, $model->scenarios())) {
            $model->scenario = $scenario;
        } else {
            $scenario = $this->viewScenario;
            $model->scenario = $scenario;
        }

        return $model;
    }
}
