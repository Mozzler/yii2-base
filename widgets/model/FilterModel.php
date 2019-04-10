<?php
namespace mozzler\base\widgets\model;

use mozzler\base\widgets\BaseWidget;
use yii\helpers\ArrayHelper;

class FilterModel extends BaseWidget {
	
	public function defaultConfig()
	{
    	return ArrayHelper::merge(parent::defaultConfig(), [
            "id" => null,
            "model" => null,
            "filterSelector" => ".btn-filter",
            "placeholder" => "Filter by {fieldLabel}",
            "hasFilter" => false,
            "submit" => [
                "url" => null
            ],
            "params" => [],
            "fieldConfigs" => [],
            "tag" => "div",
            "options" => [
                "class" => "row form-horizontal widget-model-filter"
            ],
            "form" => [
                "options" => []
            ],
            "container" => [
                "tag" => "div",
                "options" => [
                    "class" => "col-md-3"
                ]
            ],
            'buttonsContainer' => [
				'tag' => 'div',
				'options' => [
					'class' => 'buttons'
				]
			],
            "row" => [
                "tag" => "div",
                "limit" => 4,
                "options" => [
                    "class" => "row"
                ]
            ],
            "clear" => [
                "tag" => "a",
                "options" => [
                    "class" => "btn btn-sm btn-default btn-clear"
                ],
                "url" => "{{ widget.model.getUrl('index') }}",
                "label" => "Clear"
            ],
            "submit" => [
                "tag" => "button",
                "options" => [
                    "type" => 'submit',
                    "class" => "btn btn-sm btn-primary btn-submit"
                ],
                "label" => "Search"
            ]
        ]);
	}
    
    /**
     * Force templatify
     */
	public function config($templatify=true)
	{
    	return parent::config(true);
	}
}

?>