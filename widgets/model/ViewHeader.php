<?php
namespace mozzler\base\widgets\model;

use mozzler\base\widgets\BaseWidget;

class ViewHeader extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'row widget-model-view-header'
			],
			'container' => [
				'tag' => 'div',
				'options' => [
					'class' => 'col-md-12'
				]
			],
		];
	}
	
	public function config($templatify=false) {
		$config = parent::config($templatify);
		
		$config['emptyModel'] = \Yii::createObject($config['model']::className());
		
		return $config;
	}
	
}

?>