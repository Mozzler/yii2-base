<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;

class RenderFieldLayout extends BaseField {
	
	public function defaultConfig() {
		return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
		    "tag" => "div",
		    "options" => [
		        "class" => "widget-model-field-render-view"
		    ],
		    "model" => null,
		    "attribute" => null,
		    "content" => null,
		    "emptyValue" => "-",
		    "label" => [
		        "postFix" => ":"
		    ],
		    "tooltip" => [
		        "enabled" => true,
		        "placement" => "top",
		        "content" => null
		    ],
		    "template" => "{{ widget.label }}\n{{ widget.content }}",
		]);
	}
	
	public function config($templatify=true) {
		$config = parent::config($templatify);
		$config['field'] = $config['model']->getModelField($config['attribute']);
		
		if (!$config['tooltip']['content']) {
			$config['tooltip']['content'] = $config['field']->hint;
		}
		
		if (!$config['tooltip']['content']) {
		    $config['tooltip']['enabled'] = false;
		}
		
		$this->outputJsData([
			'tooltip' => [
				'enabled' => $config['tooltip']['enabled']
			]
		]);
		
		return $config;
	}
	
}

?>