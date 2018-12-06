<?php
namespace mozzler\base\widgets\model;

use mozzler\base\widgets\BaseWidget;

class ViewModel extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'row widget-model-view'
			],
			'container' => [
				'tag' => 'div',
				'options' => [
					'class' => 'col-md-12'
				]
			],
			'model' => null,
			'panelConfig' => [
				'heading' => [
					'title' => [
						'content' => '<h3>{{ widget.model.getModelConfig(\"label\") }}</h3>'
					]
				],
				'body' => [],
				'footer' => false
			]
		];
	}
	
	// take $config and process it to generate final config
	/*public function code() {
		$config = $this->config();
		
		return $config;
	}*/
	
}

?>