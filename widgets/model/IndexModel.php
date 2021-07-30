<?php
namespace mozzler\base\widgets\model;

use mozzler\base\widgets\BaseWidget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use mozzler\base\helpers\WidgetHelper;
use yii\data\ActiveDataProvider;

class IndexModel extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'widget-model-index'
            ],
            'dataProvider' => null,
            'model' => null,
			'container' => [
				'tag' => 'div',
				'options' => [
					'class' => 'col-md-12'
				]
            ],
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
		];
	}
	
	// take $config and process it to generate final config
	public function code($templatify = false) {
		$config = $this->config();
		$t = new \mozzler\base\components\Tools;
        
        $model = $config['model'];
        if (!isset($config['dataProvider'])) {
			$config['dataProvider'] = new ActiveDataProvider([
				'query' => $model->find(),
			]);
		}
		
        $dataProvider = $config['dataProvider'];
        
        $columns = $this->buildColumns($model);
		
		$config = ArrayHelper::merge([
			'gridViewConfig' => [
				'dataProvider' => $dataProvider,
				'columns' => $columns
			]
		], $config);

		if ($config['applyRbacToActionColumn']) {
			$config = $this->applyRbacToActionColumn($config);
		}

        $config['canReportModel'] = \Yii::$app->rbac->canAccessModel($config['model'], 'report'); // Don't show the reports panel if you can't view it
		$finalConfig = WidgetHelper::templatifyConfig($config, ['widget' => $config]);

		// exclude gridViewConfig['columns'] from the templatify as they should be 
		// processed separately by each widget
		$finalConfig['gridViewConfig']['columns'] = $config['gridViewConfig']['columns'];
		
		return $finalConfig;
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

