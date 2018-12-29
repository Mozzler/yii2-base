<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;

class MultiSelectField extends BaseField {
	
	public function config($templatify=true)
	{
		$config = parent::config();
		
		$modelField = $config['model']->getModelField($config['attribute']);
		$attribute = $config['attribute'];
		$value = $config['model']->$attribute;
		
		if ($value) {
			$labels = $modelField->getOptionLabels($value);
			$config['displayValues'] = join($labels, ', ');
		} else {
			$config['displayValues'] = '';
		}
		
		
		
		return $config;
	}
	
}

?>