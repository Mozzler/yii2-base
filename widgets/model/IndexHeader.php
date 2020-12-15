<?php
namespace mozzler\base\widgets\model;

use mozzler\base\widgets\BaseWidget;

class IndexHeader extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'row widget-model-index-header'
			],
			'container' => [
				'tag' => 'div',
				'options' => [
					'class' => 'col-md-12'
				]
			],
			'buttonsContainer' => [
				'tag' => 'div',
				'options' => [
					'class' => 'col-md-12 buttons'
				],
				'filter' => [
					'options' => [
						'class' => 'btn btn-default btn-sm btn-filter',
						'title' => 'Filter the results'
					],
					'text' => '<span class="glyphicon glyphicon-filter"></span>'
				]
			],
		];
	}
	
	public function config($templatify=false) {
		$config = parent::config($templatify);
		
		$config['model']->scenario = $config['model']::SCENARIO_SEARCH;
		$config['model']->load(\Yii::$app->request->get());
		$config['canCreateModel'] = \Yii::$app->rbac->canAccessModel($config['model'], 'insert');
		$config['canExportModel'] = \Yii::$app->rbac->canAccessModel($config['model'], 'export');
		
		$config['exportUrl'] = $config['model']->getUrl("export", \Yii::$app->request->get());

		return $config;
	}
	
}

