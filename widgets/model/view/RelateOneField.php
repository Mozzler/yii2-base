<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;

class RelateOneField extends BaseField {
	
    public function defaultConfig() {
		return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
		    "tag" => "a",
		    "options" => [
                "class" => "",
                "href" => "{{ widget.model.getRelated(widget.attribute).getUrl('view') }}"
            ],
            "label" => "{{ widget.model.getRelated(widget.attribute).name }}",
		    "model" => null,
		    "attribute" => null
		]);
    }
    
    public function config($templatify=true)
	{
        $config = parent::config(true);
        
		return $config;
    }
	
}

?>
