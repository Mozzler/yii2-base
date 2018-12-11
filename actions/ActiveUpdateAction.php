<?php
namespace mozzler\base\actions;

class ActiveUpdateAction extends \yii\rest\UpdateAction
{
    public $viewScenario = Model::SCENARIO_VIEW;
    
    public function run()
    {
	    $model = parent::run();

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
