<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;
use Yii;
use yii\helpers\Url;
use yii\web\ServerErrorHttpException;

class ActiveDeleteManyAction extends \yii\rest\Action
{

    public function run()
    {
        // Get our list of models
        $modelIds = \Yii::$app->request->post('ids');

        if (!is_array($modelIds) || sizeof($modelIds) == 0) {
            throw new \yii\web\BadRequestHttpException('Unable to find array of ids to delete');
        }

        // Track results
        $result = [
            'deleted' => []
        ];

        // Fetch alll the models, throwing an exception if invalid Mongo ID's specified
        $models = \Yii::$app->t->getModels($this->modelClass, ["_id" => $modelIds]);

        // Delete all the models. We delete them manually so that any "ON DELETE"
        // events get executed
        foreach ($models as $model) {
            if ($model->delete()) {
                $result['deleted'][] = $model->id;
                \Yii::trace("Deleted model: ".$model->id);
            } else {
                \Yii::warning("Unable to delete model: ".$model->id);
            }
        }

        return $result;
    }
    
}
