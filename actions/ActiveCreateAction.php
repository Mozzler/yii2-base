<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;
use Yii;
use yii\helpers\Url;
use yii\web\ServerErrorHttpException;

class ActiveCreateAction extends \yii\rest\CreateAction
{
	
    public $scenario = [Model::SCENARIO_CREATE_API, Model::SCENARIO_CREATE];

    public $resultScenario = [Model::SCENARIO_VIEW_API, Model::SCENARIO_VIEW];
	
	public function run()
    {
        /* @var $model \yii\db\ActiveRecord */
        $model = new $this->modelClass();
        $model->scenario = $this->scenario;

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $id = implode(',', array_values($model->getPrimaryKey(true)));
            $response->getHeaders()->set('Location', $model->getUrl($this->viewAction));
            $model->scenario = $this->resultScenario;
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }

        return $model;
    }
}
