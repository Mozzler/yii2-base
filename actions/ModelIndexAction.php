<?php
namespace mozzler\base\actions;

use yii\helpers\ArrayHelper;

use mozzler\base\models\Model;
use yii\helpers\Html;
use mozzler\base\helpers\WidgetHelper;

class ModelIndexAction extends BaseModelAction
{
	public $id = 'index';
    
    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */

    public $scenario = Model::SCENARIO_LIST;
    
    public function defaultConfig() {
	    return ArrayHelper::merge(parent::defaultConfig(),[
		    'gridViewConfig' => [
			    'columns' => [
				    ['class' => '\kartik\grid\ActionColumn']
			    ],
			    'panel' => [
				    'heading' => '{{ widget.model.getModelConfig("labelPlural") }}'
			    ],
			    'toolbar' => [
			    ]
			],
			'applyRbacToActionColumn' => true
	    ]);
    }

    /**
     */
    public function run()
    {   
        $model = $this->controller->getModel();
        $model->setScenario($this->scenario);
        
        $dataProvider = $model->search(\Yii::$app->request->get());
		$columns = $this->buildColumns($model);
		
		$config = ArrayHelper::merge([
			'gridViewConfig' => [
				'dataProvider' => $dataProvider,
				'columns' => $columns
			]
		], $this->config());

		if ($config['applyRbacToActionColumn']) {
			$config = $this->applyRbacToActionColumn($config, $model);
		}
		
		$config['model'] = $model;
		$config = WidgetHelper::templatifyConfig($config, ['widget' => $config]);
        
        $this->controller->templateData['config'] = $config;
		$this->controller->templateData['model'] = $model;

		if ($this->controller->jsonRequested) {
			$this->controller->data['items'] = $dataProvider->getModels();
		}
        
        return parent::run();
    }
    
    protected function buildColumns($model) {
	    $base = \Yii::$app->getModule('mozzlerBase');
		$fieldGridConfig = $base->fieldGridConfig;
		
	    $attributes = $model->activeAttributes();
		$columns = [];
		foreach ($attributes as $attribute) {
			$field = $model->getModelField($attribute);
			if (!$field) {
				\Yii::warning("Unable to locate field for requested attribute ($attribute)");
				continue;
			}
			
			$customFieldConfig = [];
			$columns[] = $fieldGridConfig->getFieldConfig($field, $customFieldConfig);
		}
		
		return $columns;
	}
	
	/**
	 * Toggle the display of view, update, delete buttons
	 * depending on if the current user has access to perform
	 * those operations on the model.
	 */
	protected function applyRbacToActionColumn($config, $model) {
		$columnsCount = sizeof($config['gridViewConfig']['columns']);

		$config['gridViewConfig']['columns'][$columnsCount-1]['visibleButtons'] = [
			'view' => function($model, $key, $index) {
				return \Yii::$app->rbac->canAccessModel($model, 'find');
			},
			'update' => function($model, $key, $index) {
				return \Yii::$app->rbac->canAccessModel($model, 'update');
			},
			'delete' => function($model, $key, $index) {
				return \Yii::$app->rbac->canAccessModel($model, 'delete');
			}
		];
		return $config;
	}
}