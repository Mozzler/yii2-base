<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;

class ActiveIndexAction extends \yii\rest\IndexAction
{
	
    public $scenario = Model::SCENARIO_LIST;
   
    public function init() {
        parent::init();

        $this->dataFilter = [
            'class' => 'yii\data\ActiveDataFilter',
            'searchModel' => $this
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
