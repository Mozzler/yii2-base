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
    public function run()
    {
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
        $emptyModel = \Yii::createObject($modelClass);
        $modelClass = $emptyModel::className();
        $keys = $modelClass::primaryKey();

        $queryFilter = [];

        if (count($keys) > 1) {
            $values = explode(',', $id);
            if (count($keys) === count($values)) {
                $queryFilter = array_combine($keys, $values);
            }
        } elseif ($id !== null) {
            $queryFilter = [
                '_id' => $id
            ];
        }

        $query = $modelClass::find();
        $query->andWhere($queryFilter);

        // Apply RBAC filter to the find model query
        $rbacFilter = \Yii::$app->rbac->canAccessAction($this);
        if ($rbacFilter && is_array($rbacFilter)) {
            $query->andWhere($rbacFilter);
        }

        $model = $query->one();

        if (isset($model)) {
            return $model;
        } else {
            throw new NotFoundHttpException("Object not found: $id");
        }
    }


    /**
     * @param null $model
     * @return \yii\db\ActiveRecord
     *
     * Loads up the model and the defaults
     * Used in the Create and Update Actions
     */
    public function loadModel($model = null)
    {

        if (is_null($model)) {
            // If it's a create action
            /* @var $model \yii\db\ActiveRecord */
            $model = $this->controller->getModel();
        }
        $model->setScenario($this->scenario);

        // Load default values
        $model->loadDefaultValues();

        // Populate the model with any GET data
        $model->load(\Yii::$app->request->get(), "");

        return $model;
    }
}


