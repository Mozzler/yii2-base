<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;

class ActiveViewAction extends \yii\rest\ViewAction
{
	public $scenario = Model::SCENARIO_VIEW;
	
	public function run($id) {
		$item = parent::run($id);
		
		return [
			'item' => $item
		];
	}
	
    public function findModel($id)
    {
        $model = parent::findModel($id);
        $scenario = $this->scenario.'-api';

        // check for an "-api" scenario
        if (in_array($scenario, $model->scenarios())) {
            $model->scenario = $scenario;
        } else {
            $scenario = $this->scenario;
            $model->scenario = $scenario;
        }

        return $model;
    }
}
