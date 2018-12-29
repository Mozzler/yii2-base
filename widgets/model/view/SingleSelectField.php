<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;

class SingleSelectField extends BaseField {
	
	public function config($templatify=true)
	{
		$config = parent::config();
		
		$modelField = $config['model']->getModelField($config['attribute']);
		$attribute = $config['attribute'];
		
		$config['displayValue'] = $modelField->options[$config['model']->$attribute];
		
		return $config;
	}
	
}

?>