<?php
namespace mozzler\base\actions;

use yii\web\NotFoundHttpException;

class BaseModelAction extends BaseAction
{
	public $id = 'model';
	
    public $findModel;
    
    /**
     * Return data as JSON if accept content type is set to `application/json`
     */
    public function run() {
        if ($this->controller->jsonRequested) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            // TODO: Test this return $this->controller->asJson($this->controller->data);

            return $this->controller->data;
        }

        return parent::run();
    }
	
	/**
     * Returns the data model based on the primary key given.
     * If the data model is not found, a 404 HTTP exception will be raised.
     * @param string $id the ID of the model to be loaded. If the model has a composite primary key,
     * the ID must be a string of the primary key values separated by commas.
     * The order of the primary key values should follow that returned by the `primaryKey()` method
     * of the model.
     * @return ActiveRecordInterface the model found
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function findModel($id)
    {
        if ($this->findModel !== null) {
            return call_user_func($this->findModel, $id, $this);
        }

        /* @var $modelClass ActiveRecordInterface */
        $modelClass = $this->controller->modelClass;
        $keys = $modelClass::primaryKey();
        if (count($keys) > 1) {
            $values = explode(',', $id);
            if (count($keys) === count($values)) {
                $model = $modelClass::findOne(array_combine($keys, $values));
            }
        } elseif ($id !== null) {
            $model = $modelClass::findOne($id);
        }

        if (isset($model)) {
            return $model;
        } else {
            throw new NotFoundHttpException("Object not found: $id");
        }
    }
}


