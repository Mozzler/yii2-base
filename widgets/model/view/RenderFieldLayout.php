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
		    "labelConfig" => [
		        "postFix" => ":"
		    ],
		    "tooltip" => [
		        "enabled" => true,
		        "placement" => "top",
		        "content" => null
		    ],
		    "template" => "{{ widget.label }}{{ widget.label.postFix }}</label>\n{{ widget.value }}",
		    "view" => [
                "tag" => "div",
                "options" => [
                    "class" => "widget-model-field-view"
                ],
                "widgetConfig" => []
            ]
		]);
	}
	
	public function config($templatify=false) {
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

