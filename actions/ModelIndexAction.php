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
    public $dataProviderConfig = [];

    public function defaultConfig() {
        return ArrayHelper::merge(parent::defaultConfig(),[
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
        $dataProvider = $model->search(\Yii::$app->request->get(), null, $rbacFilter ? $rbacFilter : null, $this->dataProviderConfig);

        $this->controller->templateData['dataProvider'] = $dataProvider;
        $this->controller->templateData['model'] = $model;

        if ($this->controller->jsonRequested) {
            $this->controller->data['items'] = $dataProvider->getModels();
        }

        return parent::run();
    }

}