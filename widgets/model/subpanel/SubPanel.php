<?php
namespace mozzler\base\widgets\model\subpanel;

use mozzler\base\widgets\BaseWidget;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;
use mozzler\base\helpers\WidgetHelper;

class SubPanel extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'row widget-model-subpanels-subpanel'
			],
			'dataProvider' => null,
            'scenario' => 'subpanel,list',
            'panelConfig' => [
                'options' => [
                    'widget-model-subpanels-subpanel-panel col-md-12'
                ]
			],
			'gridViewConfig' => [
			    'columns' => [
				    [
						'class' => '\kartik\grid\ActionColumn',
						'urlCreator' => function($action, $model, $key, $index) {
							return $model->getUrl($action, ['id' => $model->id]);
						}
					]
				],
				'layout' => '{items}'
			],
			'container' => [
				'tag' => 'div',
				'options' => [
					'class' => 'col-md-12'
				]
			],
			'header' => [
				'tag' => 'div',
				'options' => [
					'class' => 'row widget-model-subpanels-subpanel-header'
				],
				'heading' => [
					'tag' => 'div',
					'options' => [
						'class' => 'col-md-6 widget-model-subpanels-subpanel-header-heading'
					],
					'content' => '<h2>{{ widget.emptyRelatedModel.getModelConfig("labelPlural") }}</h2>',
				],
				'buttons' => [
					'tag' => 'div',
					'options' => [
						'class' => 'col-md-6 text-right widget-model-subpanels-subpanel-buttons'
					],
					'items' => [
						'create' => "<a href=\"{{ widget.emptyRelatedModel.getUrl('create', {(widget.relatedField.relatedField): widget.model.id}) }}\" class=\"btn btn-primary btn-sm\">Create {{ widget.emptyRelatedModel.getModelConfig('label') }}</a>"
					]
				]
			],
			'applyRbacToActionColumn' => true,
			'model' => null,
			'relationAttribute' => null,
			'relationConfig' => [
				'limit' => 20,
				'filter' => [],
				'offset' => null,
				'orderBy' => [],
				'fields' => null,
				'checkPermissions' => true
			],
			'relateConfigs' => [],
		];
	}
	
	// take $config and process it to generate final config
	public function code($templatify = false) {
		$config = $this->config();
		$t = new \mozzler\base\components\Tools;
        
		$model = $config['model'];
		
		// handle scenario?
		
		if (!isset($config['dataProvider'])) {
			$relationConfig = $config['relationConfig'];
			$relatedModels = $model->getRelated($config['relationAttribute'], $relationConfig['filter'], $relationConfig['limit'], $relationConfig['offset'], $relationConfig['orderBy'], $relationConfig['fields'], $relationConfig['checkPermissions']);

			$config['dataProvider'] = new ArrayDataProvider([
				'allModels' => ArrayHelper::index($relatedModels, 'id')
			]);
		}

		$dataProvider = $config['dataProvider'];
		
		$relatedField = $model->getModelField($config['relationAttribute']);
		$config['relatedField'] = $relatedField;
		$emptyRelatedModel = $t->createModel($relatedField->relatedModel);
		$emptyRelatedModel->setScenario($config['scenario']);
		$config['emptyRelatedModel'] = $emptyRelatedModel;

        $columns = $this->buildColumns($emptyRelatedModel);
		
		$config = ArrayHelper::merge([
			'gridViewConfig' => [
				'dataProvider' => $dataProvider,
				'columns' => $columns
			]
		], $config);

		if ($config['applyRbacToActionColumn']) {
			$config = $this->applyRbacToActionColumn($config);
		}
		
		$config = WidgetHelper::templatifyConfig($config, ['widget' => $config]);
		
		return $config;
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
	protected function applyRbacToActionColumn($config) {
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