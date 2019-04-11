<?php

namespace mozzler\base\actions;

use mozzler\base\models\Model;
use Yii;
use yii\data\ActiveDataProvider;

class ActiveIndexAction extends \yii\rest\IndexAction
{

    public $scenario = [Model::SCENARIO_LIST_API, Model::SCENARIO_LIST];
    public $resultScenario = [Model::SCENARIO_LIST_API, Model::SCENARIO_LIST];

    public $pageSizeMaxLimit = 500;

    /*
    * Prepares the data provider that should return the requested collection of the models.
    * @return ActiveDataProvider
    *
    * Allows up to the 100 results per page not the default 50
     *
     * Based off: vendor/yiisoft/yii2/rest/IndexAction.php:prepareDataProvider()
    */
    protected function prepareDataProvider()
    {
        $requestParams = Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = Yii::$app->getRequest()->getQueryParams();
        }

        $filter = null;
        if ($this->dataFilter !== null) {
            $this->dataFilter = Yii::createObject($this->dataFilter);
            if ($this->dataFilter->load($requestParams)) {
                $filter = $this->dataFilter->build();
                if ($filter === false) {
                    return $this->dataFilter;
                }
            }
        }

        if ($this->prepareDataProvider !== null) {
            return call_user_func($this->prepareDataProvider, $this, $filter);
        }

        /* @var $modelClass \yii\db\BaseActiveRecord */
        $modelClass = $this->modelClass;

        $query = $modelClass::find();
        if (!empty($filter)) {
            $query->andWhere($filter);
        }

        return Yii::createObject([
            'class' => ActiveDataProvider::className(),
            'query' => $query,
            'pagination' => [
                'pageSizeLimit' => [1, $this->pageSizeMaxLimit],
                'params' => $requestParams,
            ],
            'sort' => [
                'params' => $requestParams,
            ],
        ]);
    }

}
