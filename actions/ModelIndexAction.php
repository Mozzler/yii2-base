<?php

namespace mozzler\base\actions;

use yii\helpers\ArrayHelper;

use mozzler\base\models\Model;

class ModelIndexAction extends BaseModelAction
{
    public $id = 'index';

    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */

    public $scenario = Model::SCENARIO_LIST;
    public $dataProviderConfig = [
        'pagination' => [
            'pageSizeLimit' => 500, // Allow a lot higher maximum page size by default (which is only 50) use ?per-page=500 to actually see that
        ]
    ];

    public function defaultConfig()
    {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'widgetConfig' => []
        ]);
    }

    /**
     */
    public function run()
    {
        $model = $this->controller->getModel();
        $model->setScenario($this->scenario);

        $rbacFilter = \Yii::$app->rbac->canAccessAction($this);

        $sort = null;
        if (!empty(\Yii::$app->request->get('sort'))) {
            $sort = \Yii::$app->request->get('sort');
        }
        if (!empty($sort) && is_string($sort)) {
            // e.g 'createdAt'
            $sortOrder = SORT_ASC; // Default ascending
            $sortEntry = $sort;
            // e.g '-updatedAt'
            if ('-' === substr($sort, 0, 1)) {
                $sortEntry = substr($sort, 1, strlen($sort) -1);
                $sortOrder = SORT_DESC;
            }
            $sort = [$sortEntry => $sortOrder];
        }

        $dataProvider = $model->search(\Yii::$app->request->get(), null, $rbacFilter ? $rbacFilter : null, $this->dataProviderConfig, $sort);

        $this->controller->templateData['dataProvider'] = $dataProvider;
        $this->controller->templateData['model'] = $model;

        if ($this->controller->jsonRequested) {
            $this->controller->data['items'] = $dataProvider->getModels();
        }

        return parent::run();
    }

}