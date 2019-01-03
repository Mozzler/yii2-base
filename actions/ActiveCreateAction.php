<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;
use Yii;
use yii\helpers\Url;
use yii\web\ServerErrorHttpException;

class ActiveCreateAction extends \yii\rest\CreateAction
{
	
	public $viewScenario = Model::SCENARIO_VIEW;
	
	public function run()
    {
        /* @var $model \yii\db\ActiveRecord */
        $model = new $this->modelClass([
            'scenario' => $this->scenario,
        ]);

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $id = implode(',', array_values($model->getPrimaryKey(true)));
            $response->getHeaders()->set('Location', $model->getUrl($this->viewAction));
            $model = $this->returnModelView($model);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }

        return $model;
    }
	
	protected function returnModelView($model) {
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
