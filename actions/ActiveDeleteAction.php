<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;
use Yii;
use yii\helpers\Url;
use yii\web\ServerErrorHttpException;

class ActiveDeleteAction extends \yii\rest\DeleteAction
{
    public $scenario = Model::SCENARIO_DELETE;
    public $viewScenario = Model::SCENARIO_DELETE;

    public function run($id)
    {
        $model = $this->findModel($id);

        if ($model && $model->delete()) {
            return true;
        }
        return parent::run();
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
