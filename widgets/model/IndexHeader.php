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
				]
			],
		];
	}
	
	public function config($templatify=false) {
		$config = parent::config($templatify);
		
		$config['emptyModel'] = \Yii::createObject($config['model']::className());
		$config['canCreateModel'] = \Yii::$app->rbac->canAccessModel($config['emptyModel'], 'create');
		
		return $config;
	}
	
}

?>